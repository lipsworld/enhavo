<?php
/**
 * ArticleMenuBuilder.php
 *
 * @since 21/09/16
 * @author gseidel
 */

namespace Enhavo\Bundle\ArticleBundle\Menu;

use Enhavo\Bundle\AppBundle\Menu\Menu\BaseMenu;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleMenu extends BaseMenu
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'icon' => 'open-book',
            'label' => 'article.label.article',
            'translationDomain' => 'EnhavoArticleBundle',
            'route' => 'enhavo_article_article_index',
            'role' => 'ROLE_ENHAVO_ARTICLE_ARTICLE_INDEX'
        ]);
    }

    public function getType()
    {
        return 'article';
    }
}