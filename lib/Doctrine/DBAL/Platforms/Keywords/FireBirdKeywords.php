<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 20.08.14
 * Time: 17:45
 */

namespace Doctrine\DBAL\Platforms\Keywords;


class FireBirdKeywords extends KeywordList{

    /**
     * Returns the name of this keyword list.
     *
     * @return string
     */
    public function getName() {
        return 'firebird';
    }

    /**
     * Returns the list of keywords.
     *
     * @return array
     */
    protected function getKeywords() {
        return array();
    }
} 