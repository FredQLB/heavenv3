<?php

namespace App\Helpers;

class Pagination
{
    private $query;
    private $params;
    private $currentPage;
    private $perPage;
    private $totalItems;
    private $totalPages;
    private $results;

    public function __construct($query, $params = [], $currentPage = 1, $perPage = 20)
    {
        $this->query = $query;
        $this->params = $params;
        $this->currentPage = max(1, (int)$currentPage);
        $this->perPage = max(1, (int)$perPage);
        
        $this->calculateTotalItems();
        $this->calculateTotalPages();
        $this->loadResults();
    }

    private function calculateTotalItems()
    {
        // Créer une requête de comptage
        $countQuery = $this->buildCountQuery($this->query);
        
        try {
            $result = Database::fetch($countQuery, $this->params);
            $this->totalItems = $result['total'] ?? 0;
        } catch (\Exception $e) {
            Logger::error('Erreur lors du comptage pour pagination', [
                'query' => $countQuery,
                'error' => $e->getMessage()
            ]);
            $this->totalItems = 0;
        }
    }

    private function buildCountQuery($originalQuery)
    {
        // Retirer ORDER BY, LIMIT, OFFSET de la requête pour le comptage
        $query = preg_replace('/\s+ORDER\s+BY\s+[^)]*$/i', '', $originalQuery);
        $query = preg_replace('/\s+LIMIT\s+\d+(\s+OFFSET\s+\d+)?$/i', '', $query);
        
        // Si la requête contient GROUP BY, on doit compter différemment
        if (preg_match('/\s+GROUP\s+BY\s+/i', $query)) {
            return "SELECT COUNT(*) as total FROM ({$query}) as subquery";
        } else {
            // Remplacer SELECT ... FROM par SELECT COUNT(*) FROM
            $query = preg_replace('/SELECT\s+.*?\s+FROM/is', 'SELECT COUNT(*) as total FROM', $query, 1);
        }
        
        return $query;
    }

    private function calculateTotalPages()
    {
        $this->totalPages = ceil($this->totalItems / $this->perPage);
    }

    private function loadResults()
    {
        $offset = ($this->currentPage - 1) * $this->perPage;
        $paginatedQuery = $this->query . " LIMIT {$this->perPage} OFFSET {$offset}";
        
        try {
            $this->results = Database::fetchAll($paginatedQuery, $this->params);
        } catch (\Exception $e) {
            Logger::error('Erreur lors du chargement des résultats paginés', [
                'query' => $paginatedQuery,
                'error' => $e->getMessage()
            ]);
            $this->results = [];
        }
    }

    public function getResults()
    {
        return $this->results;
    }

    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    public function getPerPage()
    {
        return $this->perPage;
    }

    public function getTotalItems()
    {
        return $this->totalItems;
    }

    public function getTotalPages()
    {
        return $this->totalPages;
    }

    public function hasPages()
    {
        return $this->totalPages > 1;
    }

    public function hasPreviousPage()
    {
        return $this->currentPage > 1;
    }

    public function hasNextPage()
    {
        return $this->currentPage < $this->totalPages;
    }

    public function getPreviousPage()
    {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }

    public function getNextPage()
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    public function getFirstItem()
    {
        if ($this->totalItems === 0) {
            return 0;
        }
        return (($this->currentPage - 1) * $this->perPage) + 1;
    }

    public function getLastItem()
    {
        if ($this->totalItems === 0) {
            return 0;
        }
        return min($this->currentPage * $this->perPage, $this->totalItems);
    }

    /**
     * Générer les numéros de pages à afficher
     * @param int $range Nombre de pages à afficher de chaque côté de la page courante
     * @return array
     */
    public function getPageRange($range = 2)
    {
        $start = max(1, $this->currentPage - $range);
        $end = min($this->totalPages, $this->currentPage + $range);
        
        // Ajuster pour avoir toujours le même nombre de pages si possible
        if ($end - $start < $range * 2) {
            if ($start === 1) {
                $end = min($this->totalPages, $start + ($range * 2));
            } else {
                $start = max(1, $end - ($range * 2));
            }
        }
        
        return range($start, $end);
    }

    /**
     * Générer une URL pour une page donnée
     * @param int $page
     * @param array $additionalParams
     * @return string
     */
    public function getPageUrl($page, $additionalParams = [])
    {
        $params = array_merge($_GET, $additionalParams, ['page' => $page]);
        
        // Retirer la page si c'est la première
        if ($page === 1) {
            unset($params['page']);
        }
        
        $queryString = http_build_query($params);
        $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
        
        return $baseUrl . ($queryString ? '?' . $queryString : '');
    }

    /**
     * Obtenir les informations de pagination sous forme de tableau
     * @return array
     */
    public function toArray()
    {
        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total_items' => $this->totalItems,
            'total_pages' => $this->totalPages,
            'first_item' => $this->getFirstItem(),
            'last_item' => $this->getLastItem(),
            'has_previous' => $this->hasPreviousPage(),
            'has_next' => $this->hasNextPage(),
            'previous_page' => $this->getPreviousPage(),
            'next_page' => $this->getNextPage(),
            'page_range' => $this->getPageRange(),
        ];
    }

    /**
     * Créer une instance simple sans requête complexe
     * @param array $items
     * @param int $currentPage
     * @param int $perPage
     * @return static
     */
    public static function fromArray($items, $currentPage = 1, $perPage = 20)
    {
        $totalItems = count($items);
        $currentPage = max(1, (int)$currentPage);
        $perPage = max(1, (int)$perPage);
        
        $offset = ($currentPage - 1) * $perPage;
        $paginatedItems = array_slice($items, $offset, $perPage);
        
        $pagination = new static('', [], $currentPage, $perPage);
        $pagination->totalItems = $totalItems;
        $pagination->calculateTotalPages();
        $pagination->results = $paginatedItems;
        
        return $pagination;
    }

    /**
     * Générer le HTML pour la pagination
     * @param array $options
     * @return string
     */
    public function render($options = [])
    {
        if (!$this->hasPages()) {
            return '';
        }

        $showInfo = $options['show_info'] ?? true;
        $showFirstLast = $options['show_first_last'] ?? true;
        $range = $options['range'] ?? 2;

        $html = '<div class="pagination-wrapper">';
        
        if ($showInfo) {
            $html .= '<div class="pagination-info">';
            $html .= sprintf(
                'Affichage de %d à %d sur %d résultats',
                $this->getFirstItem(),
                $this->getLastItem(),
                $this->getTotalItems()
            );
            $html .= '</div>';
        }

        $html .= '<nav class="pagination">';
        $html .= '<ul class="pagination-list">';

        // Bouton Précédent
        if ($this->hasPreviousPage()) {
            $html .= sprintf(
                '<li class="pagination-item"><a href="%s" class="pagination-link pagination-prev">‹ Précédent</a></li>',
                htmlspecialchars($this->getPageUrl($this->getPreviousPage()))
            );
        } else {
            $html .= '<li class="pagination-item disabled"><span class="pagination-link pagination-prev">‹ Précédent</span></li>';
        }

        // Première page
        if ($showFirstLast && $this->currentPage > $range + 2) {
            $html .= sprintf(
                '<li class="pagination-item"><a href="%s" class="pagination-link">1</a></li>',
                htmlspecialchars($this->getPageUrl(1))
            );
            
            if ($this->currentPage > $range + 3) {
                $html .= '<li class="pagination-item disabled"><span class="pagination-link">...</span></li>';
            }
        }

        // Pages autour de la page courante
        foreach ($this->getPageRange($range) as $page) {
            if ($page === $this->currentPage) {
                $html .= sprintf(
                    '<li class="pagination-item active"><span class="pagination-link">%d</span></li>',
                    $page
                );
            } else {
                $html .= sprintf(
                    '<li class="pagination-item"><a href="%s" class="pagination-link">%d</a></li>',
                    htmlspecialchars($this->getPageUrl($page)),
                    $page
                );
            }
        }

        // Dernière page
        if ($showFirstLast && $this->currentPage < $this->totalPages - $range - 1) {
            if ($this->currentPage < $this->totalPages - $range - 2) {
                $html .= '<li class="pagination-item disabled"><span class="pagination-link">...</span></li>';
            }
            
            $html .= sprintf(
                '<li class="pagination-item"><a href="%s" class="pagination-link">%d</a></li>',
                htmlspecialchars($this->getPageUrl($this->totalPages)),
                $this->totalPages
            );
        }

        // Bouton Suivant
        if ($this->hasNextPage()) {
            $html .= sprintf(
                '<li class="pagination-item"><a href="%s" class="pagination-link pagination-next">Suivant ›</a></li>',
                htmlspecialchars($this->getPageUrl($this->getNextPage()))
            );
        } else {
            $html .= '<li class="pagination-item disabled"><span class="pagination-link pagination-next">Suivant ›</span></li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';
        $html .= '</div>';

        return $html;
    }
}
?>