<?php
// app/Views/partials/pagination.php

if (isset($pagination) && $pagination && $pagination->getTotalPages() > 1):
    $paginationData = $pagination->toArray();
?>
<div class="pagination-wrapper">
    <div class="pagination-info">
        <span class="pagination-results">
            Affichage de <?= number_format($paginationData['first_item']) ?> à <?= number_format($paginationData['last_item']) ?> 
            sur <?= number_format($paginationData['total_items']) ?> résultats
        </span>
    </div>

    <nav class="pagination" aria-label="Navigation des pages">
        <ul class="pagination-list">
            <!-- Bouton Précédent -->
            <?php if ($paginationData['has_previous']): ?>
                <li class="pagination-item">
                    <a href="<?= htmlspecialchars($pagination->getPageUrl($paginationData['previous_page'])) ?>" 
                       class="pagination-link pagination-prev" 
                       aria-label="Page précédente">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15,18 9,12 15,6"></polyline>
                        </svg>
                        Précédent
                    </a>
                </li>
            <?php else: ?>
                <li class="pagination-item disabled">
                    <span class="pagination-link pagination-prev" aria-disabled="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15,18 9,12 15,6"></polyline>
                        </svg>
                        Précédent
                    </span>
                </li>
            <?php endif; ?>

            <!-- Première page -->
            <?php if ($paginationData['current_page'] > 4): ?>
                <li class="pagination-item">
                    <a href="<?= htmlspecialchars($pagination->getPageUrl(1)) ?>" 
                       class="pagination-link" 
                       aria-label="Page 1">1</a>
                </li>
                <?php if ($paginationData['current_page'] > 5): ?>
                    <li class="pagination-item disabled">
                        <span class="pagination-link pagination-ellipsis">...</span>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Pages autour de la page courante -->
            <?php foreach ($paginationData['page_range'] as $page): ?>
                <?php if ($page === $paginationData['current_page']): ?>
                    <li class="pagination-item active">
                        <span class="pagination-link" aria-current="page" aria-label="Page <?= $page ?>, page courante">
                            <?= $page ?>
                        </span>
                    </li>
                <?php else: ?>
                    <li class="pagination-item">
                        <a href="<?= htmlspecialchars($pagination->getPageUrl($page)) ?>" 
                           class="pagination-link" 
                           aria-label="Page <?= $page ?>">
                            <?= $page ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Dernière page -->
            <?php if ($paginationData['current_page'] < $paginationData['total_pages'] - 3): ?>
                <?php if ($paginationData['current_page'] < $paginationData['total_pages'] - 4): ?>
                    <li class="pagination-item disabled">
                        <span class="pagination-link pagination-ellipsis">...</span>
                    </li>
                <?php endif; ?>
                <li class="pagination-item">
                    <a href="<?= htmlspecialchars($pagination->getPageUrl($paginationData['total_pages'])) ?>" 
                       class="pagination-link" 
                       aria-label="Page <?= $paginationData['total_pages'] ?>">
                        <?= $paginationData['total_pages'] ?>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Bouton Suivant -->
            <?php if ($paginationData['has_next']): ?>
                <li class="pagination-item">
                    <a href="<?= htmlspecialchars($pagination->getPageUrl($paginationData['next_page'])) ?>" 
                       class="pagination-link pagination-next" 
                       aria-label="Page suivante">
                        Suivant
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,18 15,12 9,6"></polyline>
                        </svg>
                    </a>
                </li>
            <?php else: ?>
                <li class="pagination-item disabled">
                    <span class="pagination-link pagination-next" aria-disabled="true">
                        Suivant
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,18 15,12 9,6"></polyline>
                        </svg>
                    </span>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<style>
.pagination-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 0;
}

.pagination-info {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.pagination-results {
    font-weight: 500;
}

.pagination {
    display: flex;
    align-items: center;
}

.pagination-list {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.pagination-item {
    display: flex;
}

.pagination-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    min-width: 40px;
    justify-content: center;
    text-decoration: none;
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    font-weight: 500;
    transition: var(--transition);
    background: var(--bg-primary);
}

.pagination-link:hover {
    background: var(--bg-secondary);
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.pagination-item.active .pagination-link {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.pagination-item.disabled .pagination-link {
    color: var(--text-muted);
    background: var(--bg-secondary);
    border-color: var(--border-color);
    cursor: not-allowed;
    opacity: 0.6;
}

.pagination-item.disabled .pagination-link:hover {
    background: var(--bg-secondary);
    border-color: var(--border-color);
    color: var(--text-muted);
}

.pagination-prev,
.pagination-next {
    font-size: 0.875rem;
}

.pagination-ellipsis {
    border: none;
    background: transparent;
    color: var(--text-muted);
    cursor: default;
}

.pagination-ellipsis:hover {
    background: transparent;
    border: none;
    color: var(--text-muted);
}

/* Version mobile simplifiée */
@media (max-width: 768px) {
    .pagination-wrapper {
        flex-direction: column;
        gap: 1rem;
    }
    
    .pagination-info {
        order: 2;
        text-align: center;
    }
    
    .pagination {
        order: 1;
    }
    
    .pagination-list {
        gap: 0.125rem;
    }
    
    .pagination-link {
        padding: 0.5rem;
        min-width: 36px;
        font-size: 0.75rem;
    }
    
    .pagination-prev,
    .pagination-next {
        padding: 0.5rem 0.75rem;
    }
    
    /* Masquer certaines pages sur mobile */
    .pagination-item:not(.active):not(:first-child):not(:last-child):not(:nth-last-child(2)):not(:nth-child(2)) {
        display: none;
    }
}

@media (max-width: 480px) {
    .pagination-prev span,
    .pagination-next span {
        display: none;
    }
    
    .pagination-link {
        min-width: 32px;
        padding: 0.375rem;
    }
}
</style>

<?php endif; ?>