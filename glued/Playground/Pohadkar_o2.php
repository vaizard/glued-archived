<?php

namespace Glued\Playground;
use Glued\Controllers\Controller;

class Pohadkar_o2 extends Controller
{
    
    // fukce co vypise prehled nahranych a formular pro nahrani dalsiho
    
    public function uploadgui($request, $response)
    {
        $vystup = '';
        // prehled nahranych zipu
        $array_of_filenames = array();
        $parent_path = '/var/www/html/glued/private/import/O2cz';
        if ($dh = opendir($parent_path)) {
            while( false !== ($file = readdir($dh))) {
                if ($file == '.' or $file == '..') { continue; }
                if (is_dir($parent_path.'/'.$file)) { $array_of_filenames[] = $file; }
            }
            closedir($dh);
        }
        if (count($array_of_filenames) == 0) {
            $vystup .= '<p>v adresari O2 nejsou zadne soubory</p>';
        }
        else {
            $vystup .= '<ul>';
            foreach ($array_of_filenames as $filename) {
                $vystup .= '<li>'.$filename.' <a href="faktura/'.$filename.'">analyzuj</a></li>';
            }
            $vystup .= '</ul>';
        }
        
        
        
        
        return $this->container->view->render($response, 'o2.twig', array('vystup' => $vystup));
    }
    
    // funkce ktera vypise analyzu jednoho diru
    public function analyzadiru($request, $response, $args)
    {
        $vystup = '';
        // prehled souboru v diru
        $array_of_filenames = array();
        $pozadovane_xml = '';
        $parent_path = '/var/www/html/glued/private/import/O2cz/'.$args['dirname'];
        if ($dh = opendir($parent_path)) {
            while( false !== ($file = readdir($dh))) {
                if ($file == '.' or $file == '..') { continue; }
                if (is_file($parent_path.'/'.$file)) {
                    $array_of_filenames[] = $file;
                    if (preg_match('|-s-mob\.xml$|', $file)) {
                        $pozadovane_xml = $file;
                    }
                }
            }
            closedir($dh);
        }
        
        // vypiseme vsechny soubory
        if (count($array_of_filenames) == 0) {
            $vystup .= '<p>v adresari nejsou zadne soubory</p>';
        }
        else {
            $vystup .= '<ul>';
            foreach ($array_of_filenames as $filename) {
                $vystup .= '<li>'.$filename.'</li>';
            }
            $vystup .= '</ul>';
        }
        
        // analyzujem soubor s-mob.xml
        if ($pozadovane_xml == '') {
            $vystup .= '<p>v adresari neni soubor koncici s-mob.xml</p>';
        }
        else {
            $obsah = file_get_contents($parent_path.'/'.$pozadovane_xml);
            
            // rozparsuju si to sam
            
            preg_match('|<summaryHead.*<\/summaryHead>|Ums', $obsah, $hlavicka);
            preg_match('|payerRefNum="([^"]*)"|', $hlavicka[0], $referefnum);
            preg_match('|to="([^"]*)" from="([^"]*)"|', $hlavicka[0], $period);
            $vystup .= '<h2 style="margin: 30px 0;">Rozpis vyúčtování referera <strong>'.$referefnum[1].'</strong> za období od <strong>'.$period[2].'</strong> do <strong>'.$period[1].'</strong></h2>';
            
            // subscriberi
            preg_match_all('|<subscriber .*<\/subscriber>|Ums', $obsah, $subscribers);
            
            foreach ($subscribers[0] as $subscriber) {
                preg_match('|phoneNumber="([^"]*)"|', $subscriber, $phonenumber);
                preg_match('|ownerRefNum="([^"]*)"|', $subscriber, $ownernumber);
                preg_match('|ownerCustCode="([^"]*)"|', $subscriber, $customcode);
                preg_match('|summaryPrice="([^"]*)"|', $subscriber, $sumprice);
                
                
                // regular charges
                $rc_vystup = '';
                $rc_real_summary = 0;
                $rc_vystup .= '<table class="table table-bordered">';
                $rc_vystup .= '<tr class="info"><th>Regular charges</th><th>from</th><th>to</th><th>price without tax</th><th>tax</th></tr>';
                preg_match('|<regularCharges.*<\/regularCharges>|Ums', $subscriber, $regularblok);
                preg_match('|rcTotalPrice="([^"]*)"|', $regularblok[0], $rcprice);
                preg_match_all('|<rcItem [^>]*>|', $regularblok[0], $rcitemy);
                foreach ($rcitemy[0] as $rcitem) {
                    $rcdata = simplexml_load_string($rcitem);
                    $rcattr = $rcdata->attributes();
                    $rc_vystup .= '<tr><td>'.$rcattr['feeName'].'</td><td>'.$rcattr['validFrom'].'</td><td>'.$rcattr['validTo'].'</td><td>'.$rcattr['priceWithoutTax'].'</td><td>'.$rcattr['tax'].'</td></tr>';
                    $rc_real_summary += (float) $rcattr['priceWithoutTax'];
                }
                $rc_vystup .= '<tr class="active"><td colspan="5"> total regular charges price: <strong>'.$rcprice[1].'</strong> (real summary: '.$rc_real_summary.')</td></tr>';
                $rc_vystup .= '</table>';
                
                
                // usage charges
                $uc_vystup = '';
                $uc_real_summary = 0;
                $testuj_usage = preg_match('|<usageCharges.*<\/usageCharges>|Ums', $subscriber, $usageblok);
                if ($testuj_usage) {
                    $uc_vystup .= '<table class="table table-bordered">';
                    $uc_vystup .= '<tr class="info"><th>Usage charges</th><td>quantity + uom</td><td>displayedUom</td><td>period</td><td>tax</td><td>priceWithoutTax</td><td>freeUnitsAmount</td></tr>';
                    preg_match('|<usageCharges.*<\/usageCharges>|Ums', $subscriber, $usageblok);
                    preg_match('|ucTotalPrice="([^"]*)"|', $usageblok[0], $ucprice);
                    // ted jednotlive charge
                    preg_match_all('|<usageCharge.*<\/usageCharge>|Ums', $usageblok[0], $subusagebloky);
                    foreach ($subusagebloky[0] as $subusage) {
                        $real_items_summary = 0;
                        preg_match('|usagePackName="([^"]*)"|', $subusage, $subname);
                        preg_match('|subtotalPrice="([^"]*)"|', $subusage, $subprice);
                        preg_match_all('|<ucItem [^>]*>|', $subusage, $ucitemy);
                        $sub_real_sumary = 0;
                        $sub_uc_vystup = '';
                        foreach ($ucitemy[0] as $ucitem) {
                            /*
                            <ucItem quantity="3840" displayedUom="min" uom="Sec" periodDescr="špička" period="02" tax="21.0" rowID="1" quantityOfConnect="21" priceWithoutTax="49.5" parentRowID="901" name="Do O2" freeUnitsPrice="0.0" freeUnitsAmount="870.0" freeCredits="0"/>
                            */
                            $ucdata = simplexml_load_string($ucitem);
                            $ucattr = $ucdata->attributes();
                            $sub_uc_vystup .= '<tr><td>'.$ucattr['name'].'</td><td>'.$ucattr['quantity'].' '.$ucattr['uom'].'</td><td>'.$ucattr['displayedUom'].'</td><td>'.$ucattr['periodDescr'].'</td><td>'.$ucattr['tax'].'</td><td>'.$ucattr['priceWithoutTax'].'</td><td>'.$ucattr['freeUnitsAmount'].'</td></tr>';
                            $sub_real_sumary += (float) $ucattr['priceWithoutTax'];
                        }
                        $uc_vystup .= '<tr><td colspan="7"><strong>'.$subname[1].', subtotal price: '.$subprice[1].'</strong> (real summary: '.$sub_real_sumary.')</td></tr>';
                        $uc_vystup .= $sub_uc_vystup;
                        $uc_real_summary += $sub_real_sumary;
                    }
                    $uc_vystup .= '<tr class="active"><td colspan="7">total usage charges price: <strong>'.$ucprice[1].'</strong> (real summary: '.$uc_real_summary.')</td></tr>';
                    $uc_vystup .= '</table>';
                }
                
                
                // free units
                
                // vystup
                
                $vystup .= '
                <div class="panel panel-primary" style="margin-top: 60px;">
                  <div class="panel-heading">Phone <strong>'.$phonenumber[1].'</strong></div>
                  <div class="panel-body">
                    Owner '.$ownernumber[1].'<br>
                    code: '.$customcode[1].'<br>
                    summary: '.$sumprice[1].' (real summary: '.($rc_real_summary + $uc_real_summary).')
                  </div>
                </div>';
                
                $vystup .= $rc_vystup;
                $vystup .= $uc_vystup;
                
            }
            
            /*
            $p = xml_parser_create();
            xml_parse_into_struct($p, $obsah, $vals);
            xml_parser_free($p);
            
            $vystup .= '<h3>soubor '.$pozadovane_xml.' prevedeny na pole</h3>';
            
            foreach ($vals as $tag_data) {
                if ($tag_data['type'] == 'open' or $tag_data['type'] == 'complete') {
                    $vystup .= '<div style="margin-left: '.($tag_data['level'] * 20).'px;">';
                    $vystup .= '<span style="color: grey;">'.$tag_data['tag'].'</span> ';
                    if (isset($tag_data['attributes'])) { $vystup .= ' <span style="color: grey;" title="atributy: '.print_r($tag_data['attributes'], true).'">[atr]</span> '; }
                    if (isset($tag_data['value'])) { $vystup .= ' : <span style="color: black; font-weight: bold;">'.$tag_data['value'].'</span> '; }
                    
                    $vystup .= '</div>';
                }
            }
            */
            
        }
        
        
        return $this->container->view->render($response, 'o2-analyza.twig', array('vystup' => $vystup, 'zipname' => $args['dirname']));
    }
    
    
    // funkce co zpracuje poslany nahravany zip a nahraje ho do stor/o2
    public function savezip($request, $response)
    {
        $files = $request->getUploadedFiles();
        if (empty($files['files'])) {
            throw new Exception('Expected uploaded files, got none.');
        }
        
        foreach ($files['files'] as $newfile) {
            if ($newfile->getError() === UPLOAD_ERR_OK) {
                $new_file = array();
                $new_file['orig_name'] = $newfile->getClientFilename();
                $new_file['mime'] = $newfile->getClientMediaType();
                $new_file['size'] = $newfile->getSize();
                
                if ($new_file['mime'] == 'application/zip') {
                    
                    $f[] = $new_file;
                    /*
                    $f[]['result'] = $newfile->getClientFilename();
                    */
                    
                    $full_path = "/var/www/html/glued/private/import/O2cz/";
                    $casti = explode('.', $new_file['orig_name']);
                    array_pop($casti);
                    $posible_dir_name = implode('.', $casti);
                    
                    $newfile->moveTo($full_path.$new_file['orig_name']);
                    
                    // rozbalime
                    $zip = new \ZipArchive;
                    $res = $zip->open($full_path.$new_file['orig_name']);
                    if ($res === TRUE) {
                        $zip->extractTo($full_path.$posible_dir_name);
                        $zip->close();
                    }
                    else {
                        $this->container->flash->addMessage('error', 'file: '.$new_file['orig_name'].' is not valid zip');
                        return $response->withRedirect($this->container->router->pathFor('o2gui'));
                    }
                }
                else {
                    $this->container->flash->addMessage('error', 'file: '.$new_file['orig_name'].' is not zip');
                    return $response->withRedirect($this->container->router->pathFor('o2gui'));
                }
            } else {
                $this->container->flash->addMessage('error', 'Some or all of your files failed to upload.');
                return $response->withRedirect($this->container->router->pathFor('o2gui'));
            }
        }
        
        $success = implode(', ', array_map(function ($entry) {
        return $entry['orig_name'].'('.$entry['mime'].', '.$entry['size'].')';
        }, $f));
        
        $this->container->flash->addMessage('info', 'All your files (' . $success . ') were successfully uploaded.');
        return $response->withRedirect($this->container->router->pathFor('o2gui'));
    }
    
    
}
