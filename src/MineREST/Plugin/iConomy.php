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

class iConomy extends MineRESTPlugin
{
    /**
     * @Route('/money/([A-Za-z0-9-_]+)')
     * @Params({'player'})
     * @Method('GET')
     */
    public function get()
    {
        if (!isset($this->data['player'])) {
            return $this->error('Parameter missing: player');
        }

        $query = $this->db()->prepare("SELECT balance FROM iconomy WHERE username = ?");
        $query->execute(array($this->data['player']));
        $data = $query->fetch();
        if ($data == null) {
            return $this->ok(array('money' => '0'));
        } else {
            return $this->ok(array('money' => $data['balance']));
        }
    }
}
