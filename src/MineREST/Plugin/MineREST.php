<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST\Plugin;
 
use MineREST\MineRESTPlugin;

class MineREST extends MineRESTPlugin {

    /**
     * @Route('/plugins')
     * @Method('GET')
     */
    public function getPlugins() {

    }

    /**
     * @Route('/plugin/(.+)')
     * @Params({'plugin'})
     * @Method('GET')
     */
    public function getPluginInfos() {

    }

    /**
     * @Route('/plugin/enable')
     * @Method('POST')
     */
    public function pluginEnable() {

    }

    /**
     * @Route('/plugin/disable')
     * @Method('POST')
     */
    public function pluginDisable() {

    }
}
