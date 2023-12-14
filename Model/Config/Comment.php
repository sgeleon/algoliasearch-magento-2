<?php

namespace Algolia\AlgoliaSearch\Model\Config;

use Magento\Framework\UrlInterface;

class Comment implements \Magento\Config\Model\Config\CommentInterface
{
    protected $urlInterface;

    public function __construct(
        UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        $url = $this->urlInterface->getUrl('algolia_algoliasearch/queuearchive/index');

        return 'Useful for debugging. Algolia archives failed jobs by default. Enable this setting to archive all jobs that are processed by the indexing queue and to obtain and preserve the stack trace for jobs created. <br /> Access the <a href="' . $url . '">Queue Archives</a>.';
    }
}