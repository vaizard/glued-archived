<?php

namespace Glued\Playground\pohadkar_platby\Controllers;
use Glued\Controllers\Controller;

class Pohadkar_platby extends Controller
{
    
    // fukce co vypise prehled plateb a odkaz na zadano nove platby
    public function list($request, $response)
    {
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
        
        
        
        return $this->container->view->render($response, '../Playground/pohadkar_platby/Views/platby.twig', array('vystup' => $vystup));
    }
    
    
    // funkce ktera vypise formular na vlozeni nove platby
    public function form($request, $response)
    {
        return $this->container->view->render($response, 'platby-new.twig');
    }
    
    
    // funkce co ulozi zadanou platbu do tabulky platby_mzdy
    /*
    povinne pole
    id (ale to se zada samo autoincrementem)
    
    - id_creator  (id uzivatele z users tabulky)
    - dt_created  (timestamp vlozeni do db)
    - sender_account  (cislo meho konta)
    - sender_bank (cislo me banky)
    - recipient_account (cislo ciloveho uctu)
    - recipient_bank (cislo cilove banky)
    - amount  (castka, mozna v halirich, nevim zatim)
    - date_due (timestamp kdy se to ma poslat)
    
    a dale tu jsou tri symboly, note a mesage
    - symbol_variable
    - symbol_constant
    - symbol_specific
    - message
    - note
    a nevyuzite files a date send pole, ktere kdovikdy se bude plnit
    */
    public function insert($request, $response)
    {
        // validaci zatim preskocime , TODO
        
        // user id vezmu z auth funkce user()
        $user_data = $this->container->auth->user();
        $user_id = $user_data['id'];
        
        // priprava pole na insert do db, TODO casy zatim vlozim aktualni
        $data = Array (
            "id_creator"     => $user_id,
            "dt_created"      => time(),
            "sender_account"      => $request->getParam('sender_account'),
            "sender_bank"      => $request->getParam('sender_bank'),
            "recipient_account"      => $request->getParam('recipient_account'),
            "recipient_bank"      => $request->getParam('recipient_bank'),
            "amount"      => $request->getParam('amount'),
            "symbol_variable"      => $request->getParam('symbol_variable'),
            "symbol_constant"      => $request->getParam('symbol_constant'),
            "symbol_specific"      => $request->getParam('symbol_specific'),
            "message"      => $request->getParam('message'),
            "note"      => $request->getParam('note'),
            "date_due"  => time()
        );
        $vlozena_platba = $this->container->db->insert('platby_mzdy', $data);
        
        // flash a presmerovani na list stranku
        if ($vlozena_platba) {
            $this->container->flash->addMessage('info', 'Nová platba byla vložena do databáze.');
        } else {
            $this->container->flash->addMessage('error', 'Novou platbu se nepodařilo vložit do databáze. Chyba: '.$this->container->db->getLastError());
        }
        
        // presmerovani
        return $response->withRedirect($this->container->router->pathFor('platbylist'));
    }
    
    
    // funkce, ktera vypise prikazovy soubor, pro jednu platbu
    public function prikaz($request, $response, $args)
    {
        $vystup = '';
        
        // nacteme jednu platbu, danou id v adrese
        $id_platby = (int) $args['id'];
        
        $this->container->db->where("id", $id_platby);
        $platba = $this->container->db->getOne('platby_mzdy');
        
        // zpracujeme do vystupu
        
        $vystup .= '<h3>platba: '.$id_platby.'</h3>';
        $vystup .= '<div>amount: '.$platba['amount'].', from '.$platba['sender_account'].'/'.$platba['sender_bank'].' to '.$platba['recipient_account'].'/'.$platba['recipient_bank'].'</div>';
        
        $vystup .= '<h3>soubor</h3>';
        
        $vystup .= '<pre>'; // mozna i code tag uvnitr
        
        // navesti
        $vystup .= 'UHL1'.date('dmy').'                    '.'1234567890'.'001'.'999'."\n";
        
        // zacatek souboru (1501 - uhrada (posilani z uctu), 1502 - inkaso (stazeni penez na ucet))
        $vystup .= '1'.' '.'1501'.' '.'001'.'000'.' '.'2010'."\n";
        
        // zacatek skupiny (odesilani z 1 uctu), cislo uctu uvedeme bud tady jednou, nebo u kazde polozky
        // nebudu ho uvadet nahore ale az v prevodech
        // dale tam ma byt celkova castka v halirich a datum splatnosti
        $vystup .= '2'.' '.($platba['amount'] * 100).' '.date('dmy')."\n";
        
        // polozky, tady mame jen jednu
        $vystup .= $platba['sender_account'].' '.$platba['recipient_account'].' '.($platba['amount'] * 100).' '.$platba['symbol_variable'].' '.$platba['recipient_bank'].$platba['symbol_constant'].' '.$platba['symbol_specific'].' '.'AV:'.$platba['message']."\n";
        
        // konec skupiny
        $vystup .= '3'.' '.'+'."\n";
        
        // vice skupin bude, pokud ma majitel vice uctu a chce odesilat i z dalsich
        
        
        // konec souboru
        $vystup .= '5'.' '.'+'."\n";
        
        $vystup .= '</pre>';
        
        return $this->container->view->render($response, 'platby-prikaz.twig', array('vystup' => $vystup));
    }
    
}
