<?php

namespace App\Services;

use App\Helpers\Config;
use App\Helpers\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private static $mailer = null;

    public static function init()
    {
        if (self::$mailer === null) {
            self::$mailer = new PHPMailer(true);
            
            $mailConfig = Config::mail();
            
            try {
                // Configuration SMTP
                self::$mailer->isSMTP();
                self::$mailer->Host = $mailConfig['host'];
                self::$mailer->SMTPAuth = true;
                self::$mailer->Username = $mailConfig['username'];
                self::$mailer->Password = $mailConfig['password'];
                self::$mailer->SMTPSecure = $mailConfig['encryption'];
                self::$mailer->Port = $mailConfig['port'];
                self::$mailer->Timeout = $mailConfig['timeout'] ?? 30;

                // Configuration générale
                self::$mailer->CharSet = 'UTF-8';
                self::$mailer->Encoding = 'base64';
                self::$mailer->setLanguage('fr');

                // Expéditeur par défaut
                $fromConfig = Config::get('mail.from');
                self::$mailer->setFrom($fromConfig['address'], $fromConfig['name']);

                Logger::info('Service Email initialisé', [
                    'host' => $mailConfig['host'],
                    'port' => $mailConfig['port']
                ]);

            } catch (Exception $e) {
                Logger::error('Erreur initialisation EmailService', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    /**
     * Envoyer un email de bienvenue au client
     */
    public static function sendWelcomeEmail($clientData)
    {
        try {
            self::init();

            $template = self::getWelcomeTemplate($clientData);
            
            self::$mailer->clearAddresses();
            self::$mailer->addAddress($clientData['email_facturation'], $clientData['raison_sociale']);
            
            self::$mailer->Subject = 'Bienvenue chez Cover AR !';
            self::$mailer->isHTML(true);
            self::$mailer->Body = $template['html'];
            self::$mailer->AltBody = $template['text'];

            self::$mailer->send();

            Logger::info('Email de bienvenue envoyé', [
                'client_email' => $clientData['email_facturation'],
                'client_name' => $clientData['raison_sociale']
            ]);

            return true;

        } catch (Exception $e) {
            Logger::error('Erreur envoi email de bienvenue', [
                'error' => $e->getMessage(),
                'client_email' => $clientData['email_facturation'] ?? 'N/A'
            ]);
            throw $e;
        }
    }

    /**
     * Envoyer les identifiants de connexion
     */
    public static function sendCredentialsEmail($email, $password, $userName = '', $isAdmin = false)
    {
        try {
            self::init();

            $template = self::getCredentialsTemplate($email, $password, $userName, $isAdmin);
            
            self::$mailer->clearAddresses();
            self::$mailer->addAddress($email, $userName);
            
            self::$mailer->Subject = 'Vos identifiants Cover AR';
            self::$mailer->isHTML(true);
            self::$mailer->Body = $template['html'];
            self::$mailer->AltBody = $template['text'];

            self::$mailer->send();

            Logger::info('Email identifiants envoyé', [
                'email' => $email,
                'user_name' => $userName,
                'is_admin' => $isAdmin
            ]);

            return true;

        } catch (Exception $e) {
            Logger::error('Erreur envoi email identifiants', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            throw $e;
        }
    }

    /**
     * Envoyer une notification d'abonnement expirant
     */
    public static function sendSubscriptionExpiringNotification($clientData, $subscriptionData, $daysLeft)
    {
        try {
            self::init();

            $template = self::getSubscriptionExpiringTemplate($clientData, $subscriptionData, $daysLeft);
            
            self::$mailer->clearAddresses();
            self::$mailer->addAddress($clientData['email_facturation'], $clientData['raison_sociale']);
            
            self::$mailer->Subject = 'Votre abonnement Cover AR expire bientôt';
            self::$mailer->isHTML(true);
            self::$mailer->Body = $template['html'];
            self::$mailer->AltBody = $template['text'];

            self::$mailer->send();

            Logger::info('Email expiration abonnement envoyé', [
                'client_email' => $clientData['email_facturation'],
                'subscription_id' => $subscriptionData['id'],
                'days_left' => $daysLeft
            ]);

            return true;

        } catch (Exception $e) {
            Logger::error('Erreur envoi email expiration', [
                'error' => $e->getMessage(),
                'client_email' => $clientData['email_facturation'] ?? 'N/A'
            ]);
            throw $e;
        }
    }

    /**
     * Envoyer une notification de facture
     */
    public static function sendInvoiceNotification($clientData, $invoiceData)
    {
        try {
            self::init();

            $template = self::getInvoiceTemplate($clientData, $invoiceData);
            
            self::$mailer->clearAddresses();
            self::$mailer->addAddress($clientData['email_facturation'], $clientData['raison_sociale']);
            
            self::$mailer->Subject = 'Nouvelle facture Cover AR - ' . $invoiceData['stripe_invoice_id'];
            self::$mailer->isHTML(true);
            self::$mailer->Body = $template['html'];
            self::$mailer->AltBody = $template['text'];

            // Ajouter le lien de téléchargement de la facture si disponible
            if (!empty($invoiceData['lien_telechargement'])) {
                self::$mailer->addAttachment($invoiceData['lien_telechargement'], 'facture.pdf');
            }

            self::$mailer->send();

            Logger::info('Email facture envoyé', [
                'client_email' => $clientData['email_facturation'],
                'invoice_id' => $invoiceData['stripe_invoice_id'],
                'amount' => $invoiceData['montant']
            ]);

            return true;

        } catch (Exception $e) {
            Logger::error('Erreur envoi email facture', [
                'error' => $e->getMessage(),
                'client_email' => $clientData['email_facturation'] ?? 'N/A'
            ]);
            throw $e;
        }
    }

    /**
     * Template email de bienvenue
     */
    private static function getWelcomeTemplate($clientData)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #000; color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .button { background: #000; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
                .info-box { background: white; padding: 20px; border-left: 4px solid #000; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Bienvenue chez Cover AR !</h1>
                </div>
                
                <div class="content">
                    <h2>Bonjour ' . htmlspecialchars($clientData['raison_sociale']) . ',</h2>
                    
                    <p>Nous sommes ravis de vous accueillir parmi nos clients !</p>
                    
                    <p>Votre compte a été créé avec succès. Vous pouvez désormais profiter de notre plateforme de réalité augmentée pour révolutionner vos présentations et démonstrations.</p>
                    
                    <div class="info-box">
                        <h3>Vos informations client :</h3>
                        <p><strong>Entreprise :</strong> ' . htmlspecialchars($clientData['raison_sociale']) . '</p>
                        <p><strong>Email :</strong> ' . htmlspecialchars($clientData['email_facturation']) . '</p>
                        <p><strong>Adresse :</strong> ' . htmlspecialchars($clientData['adresse']) . ', ' . htmlspecialchars($clientData['code_postal']) . ' ' . htmlspecialchars($clientData['ville']) . '</p>
                    </div>
                    
                    <h3>Prochaines étapes :</h3>
                    <ol>
                        <li>Vous recevrez sous peu vos identifiants de connexion</li>
                        <li>Configurez votre premier abonnement</li>
                        <li>Ajoutez vos utilisateurs</li>
                        <li>Commencez à utiliser Cover AR</li>
                    </ol>
                    
                    <p style="text-align: center;">
                        <a href="https://account.cover-ar.com" class="button">Accéder à votre espace client</a>
                    </p>
                    
                    <p>Notre équipe support reste à votre disposition pour vous accompagner dans la prise en main de la solution.</p>
                </div>
                
                <div class="footer">
                    <p>&copy; Cover AR - Tous droits réservés</p>
                    <p>Support : support@cover-ar.com | Tél : +33 1 23 45 67 89</p>
                </div>
            </div>
        </body>
        </html>';

        $text = "Bienvenue chez Cover AR !\n\n";
        $text .= "Bonjour " . $clientData['raison_sociale'] . ",\n\n";
        $text .= "Nous sommes ravis de vous accueillir parmi nos clients !\n\n";
        $text .= "Votre compte a été créé avec succès. Vous pouvez désormais profiter de notre plateforme de réalité augmentée.\n\n";
        $text .= "Vos informations client :\n";
        $text .= "- Entreprise : " . $clientData['raison_sociale'] . "\n";
        $text .= "- Email : " . $clientData['email_facturation'] . "\n";
        $text .= "- Adresse : " . $clientData['adresse'] . ", " . $clientData['code_postal'] . " " . $clientData['ville'] . "\n\n";
        $text .= "Accédez à votre espace client : https://account.cover-ar.com\n\n";
        $text .= "Support : support@cover-ar.com | Tél : +33 1 23 45 67 89";

        return ['html' => $html, 'text' => $text];
    }

    /**
     * Template email identifiants
     */
    private static function getCredentialsTemplate($email, $password, $userName, $isAdmin)
    {
        $adminInfo = $isAdmin ? '<p><strong>Interface administrateur :</strong> <a href="https://account.cover-ar.com">https://account.cover-ar.com</a></p>' : '';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #000; color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .credentials { background: white; padding: 20px; border: 2px solid #000; margin: 20px 0; text-align: center; }
                .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Vos identifiants Cover AR</h1>
                </div>
                
                <div class="content">
                    <h2>Bonjour' . ($userName ? ' ' . htmlspecialchars($userName) : '') . ',</h2>
                    
                    <p>Votre compte Cover AR a été créé. Voici vos identifiants de connexion :</p>
                    
                    <div class="credentials">
                        <h3>Identifiants de connexion</h3>
                        <p><strong>Email :</strong> ' . htmlspecialchars($email) . '</p>
                        <p><strong>Mot de passe :</strong> <code>' . htmlspecialchars($password) . '</code></p>
                    </div>
                    
                    ' . $adminInfo . '
                    
                    <div class="warning">
                        <strong>Important :</strong> Pour votre sécurité, nous vous recommandons fortement de modifier votre mot de passe lors de votre première connexion.
                    </div>
                    
                    <h3>Comment vous connecter :</h3>
                    <ol>
                        <li>Rendez-vous sur votre espace client</li>
                        <li>Saisissez votre email et mot de passe</li>
                        <li>Modifiez votre mot de passe</li>
                        <li>Commencez à utiliser Cover AR</li>
                    </ol>
                    
                    <p>En cas de problème de connexion, n\'hésitez pas à contacter notre support.</p>
                </div>
                
                <div class="footer">
                    <p>&copy; Cover AR - Tous droits réservés</p>
                    <p>Support : support@cover-ar.com | Tél : +33 1 23 45 67 89</p>
                </div>
            </div>
        </body>
        </html>';

        $text = "Vos identifiants Cover AR\n\n";
        $text .= "Bonjour" . ($userName ? ' ' . $userName : '') . ",\n\n";
        $text .= "Votre compte Cover AR a été créé. Voici vos identifiants de connexion :\n\n";
        $text .= "Email : " . $email . "\n";
        $text .= "Mot de passe : " . $password . "\n\n";
        if ($isAdmin) {
            $text .= "Interface administrateur : https://account.cover-ar.com\n\n";
        }
        $text .= "IMPORTANT : Pour votre sécurité, modifiez votre mot de passe lors de votre première connexion.\n\n";
        $text .= "Support : support@cover-ar.com | Tél : +33 1 23 45 67 89";

        return ['html' => $html, 'text' => $text];
    }

    /**
     * Template email expiration abonnement
     */
    private static function getSubscriptionExpiringTemplate($clientData, $subscriptionData, $daysLeft)
    {
        $urgencyClass = $daysLeft <= 7 ? 'urgent' : 'warning';
        $urgencyColor = $daysLeft <= 7 ? '#dc3545' : '#ffc107';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: ' . $urgencyColor . '; color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .button { background: #000; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
                .alert { background: white; padding: 20px; border-left: 4px solid ' . $urgencyColor . '; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>⚠️ Abonnement expirant</h1>
                </div>
                
                <div class="content">
                    <h2>Bonjour ' . htmlspecialchars($clientData['raison_sociale']) . ',</h2>
                    
                    <div class="alert">
                        <h3>Votre abonnement expire dans ' . $daysLeft . ' jour' . ($daysLeft > 1 ? 's' : '') . '</h3>
                        <p><strong>Formule :</strong> ' . htmlspecialchars($subscriptionData['formule_nom']) . '</p>
                        <p><strong>Date d\'expiration :</strong> ' . date('d/m/Y', strtotime($subscriptionData['date_fin'])) . '</p>
                        <p><strong>Prix mensuel :</strong> ' . number_format($subscriptionData['prix_total_mensuel'], 2) . '€</p>
                    </div>
                    
                    <p>Pour éviter toute interruption de service, renouvelez votre abonnement dès maintenant.</p>
                    
                    <p style="text-align: center;">
                        <a href="https://account.cover-ar.com/subscriptions/renew/' . $subscriptionData['id'] . '" class="button">Renouveler maintenant</a>
                    </p>
                    
                    <p>Si vous avez des questions ou souhaitez modifier votre abonnement, contactez notre équipe commerciale.</p>
                </div>
                
                <div class="footer">
                    <p>&copy; Cover AR - Tous droits réservés</p>
                    <p>Support : support@cover-ar.com | Commercial : commercial@cover-ar.com</p>
                </div>
            </div>
        </body>
        </html>';

        $text = "⚠️ Abonnement expirant\n\n";
        $text .= "Bonjour " . $clientData['raison_sociale'] . ",\n\n";
        $text .= "Votre abonnement expire dans " . $daysLeft . " jour" . ($daysLeft > 1 ? 's' : '') . "\n\n";
        $text .= "Formule : " . $subscriptionData['formule_nom'] . "\n";
        $text .= "Date d'expiration : " . date('d/m/Y', strtotime($subscriptionData['date_fin'])) . "\n";
        $text .= "Prix mensuel : " . number_format($subscriptionData['prix_total_mensuel'], 2) . "€\n\n";
        $text .= "Renouvelez dès maintenant : https://account.cover-ar.com/subscriptions/renew/" . $subscriptionData['id'] . "\n\n";
        $text .= "Support : support@cover-ar.com | Commercial : commercial@cover-ar.com";

        return ['html' => $html, 'text' => $text];
    }

    /**
     * Template email facture
     */
    private static function getInvoiceTemplate($clientData, $invoiceData)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #000; color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .button { background: #000; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
                .invoice-box { background: white; padding: 20px; border: 1px solid #ddd; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📄 Nouvelle facture</h1>
                </div>
                
                <div class="content">
                    <h2>Bonjour ' . htmlspecialchars($clientData['raison_sociale']) . ',</h2>
                    
                    <p>Une nouvelle facture est disponible pour votre compte Cover AR.</p>
                    
                    <div class="invoice-box">
                        <h3>Détails de la facture</h3>
                        <p><strong>Numéro :</strong> ' . htmlspecialchars($invoiceData['stripe_invoice_id']) . '</p>
                        <p><strong>Date :</strong> ' . date('d/m/Y', strtotime($invoiceData['date_facture'])) . '</p>
                        <p><strong>Montant :</strong> ' . number_format($invoiceData['montant'], 2) . '€</p>
                        <p><strong>Type :</strong> ' . ucfirst($invoiceData['type_facture']) . '</p>
                        ' . (!empty($invoiceData['date_echeance']) ? '<p><strong>Échéance :</strong> ' . date('d/m/Y', strtotime($invoiceData['date_echeance'])) . '</p>' : '') . '
                    </div>
                    
                    ' . (!empty($invoiceData['lien_telechargement']) ? '<p style="text-align: center;"><a href="' . htmlspecialchars($invoiceData['lien_telechargement']) . '" class="button">Télécharger la facture</a></p>' : '') . '
                    
                    <p>Le paiement sera automatiquement prélevé selon votre mode de paiement enregistré.</p>
                    
                    <p>Pour toute question concernant cette facture, contactez notre service comptabilité.</p>
                </div>
                
                <div class="footer">
                    <p>&copy; Cover AR - Tous droits réservés</p>
                    <p>Comptabilité : comptabilite@cover-ar.com | Support : support@cover-ar.com</p>
                </div>
            </div>
        </body>
        </html>';

        $text = "📄 Nouvelle facture\n\n";
        $text .= "Bonjour " . $clientData['raison_sociale'] . ",\n\n";
        $text .= "Une nouvelle facture est disponible pour votre compte Cover AR.\n\n";
        $text .= "Détails de la facture :\n";
        $text .= "- Numéro : " . $invoiceData['stripe_invoice_id'] . "\n";
        $text .= "- Date : " . date('d/m/Y', strtotime($invoiceData['date_facture'])) . "\n";
        $text .= "- Montant : " . number_format($invoiceData['montant'], 2) . "€\n";
        $text .= "- Type : " . ucfirst($invoiceData['type_facture']) . "\n";
        if (!empty($invoiceData['date_echeance'])) {
            $text .= "- Échéance : " . date('d/m/Y', strtotime($invoiceData['date_echeance'])) . "\n";
        }
        $text .= "\n";
        if (!empty($invoiceData['lien_telechargement'])) {
            $text .= "Télécharger : " . $invoiceData['lien_telechargement'] . "\n\n";
        }
        $text .= "Comptabilité : comptabilite@cover-ar.com | Support : support@cover-ar.com";

        return ['html' => $html, 'text' => $text];
    }

    /**
     * Tester la configuration email
     */
    public static function testEmailConfiguration()
    {
        try {
            self::init();
            
            $fromConfig = Config::get('mail.from');
            
            self::$mailer->clearAddresses();
            self::$mailer->addAddress($fromConfig['address']);
            
            self::$mailer->Subject = 'Test de configuration Cover AR';
            self::$mailer->Body = '<h1>Test réussi !</h1><p>La configuration email fonctionne correctement.</p>';
            self::$mailer->AltBody = 'Test réussi ! La configuration email fonctionne correctement.';
            
            self::$mailer->send();
            
            Logger::info('Test email envoyé avec succès');
            
            return true;
            
        } catch (Exception $e) {
            Logger::error('Échec test email', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Envoyer un email générique
     */
    public static function sendEmail($to, $subject, $htmlBody, $textBody = null, $attachments = [])
    {
        try {
            self::init();
            
            self::$mailer->clearAddresses();
            self::$mailer->clearAttachments();
            
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        self::$mailer->addAddress($name);
                    } else {
                        self::$mailer->addAddress($email, $name);
                    }
                }
            } else {
                self::$mailer->addAddress($to);
            }
            
            self::$mailer->Subject = $subject;
            self::$mailer->isHTML(true);
            self::$mailer->Body = $htmlBody;
            
            if ($textBody) {
                self::$mailer->AltBody = $textBody;
            }
            
            foreach ($attachments as $attachment) {
                if (is_array($attachment)) {
                    self::$mailer->addAttachment($attachment['path'], $attachment['name'] ?? '');
                } else {
                    self::$mailer->addAttachment($attachment);
                }
            }
            
            self::$mailer->send();
            
            Logger::info('Email générique envoyé', [
                'to' => $to,
                'subject' => $subject
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Logger::error('Erreur envoi email générique', [
                'error' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject
            ]);
            throw $e;
        }
    }
}
?>