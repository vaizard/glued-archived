<?php

namespace Glued\Playground;
use Glued\Controllers\Controller;

class Killua_db extends Controller
{
    
    // fukce co vypise prehled plateb a odkaz na zadano nove platby
    public function list1($request, $response)
    {
        echo "baf";
        $vystup = '';
        
        // user id vezmu z auth funkce user()
        $user_data = $this->container->auth->user();
        $user_id = $user_data['id'];
        
        // prehled plateb z db
        
        $this->container->db->where("id_creator", $user_id);
        $platby = $this->container->db->get('platby_mzdy');
        if ($this->container->db->count > 0) {
            foreach ($platby as $platba) {
                $vystup .= '<div>amount: '.$platba['amount'].', from '.$platba['sender_account'].'/'.$platba['sender_bank'].' to '.$platba['recipient_account'].'/'.$platba['recipient_bank'].' <a href="prikaz/'.$platba['id'].'">soubor</a></div>';
            }
        }
        else {
            $vystup .= '<p>žádné platby nejsou vložené</p>';
        }
        
        
        
        return $this->container->view->render($response, 'platby.twig', array('vystup' => $vystup));
    }
    
    
}
