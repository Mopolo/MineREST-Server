<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST\Plugin;

class Vanilla
{
    /**
     * @Route("/whitelist")
     * @Method("GET")
     */
    public function whitelistGet() {
        echo 'get';
    }

    /**
     * @Route("/whitelist")
     * @Method("PUT")
     */
    public function whitelistAdd() {
        echo 'add';
    }

    /**
     * @Route("/whitelist")
     * @Method("DELETE")
     */
    public function whitelistDelete() {
        echo 'delete';
    }
}
