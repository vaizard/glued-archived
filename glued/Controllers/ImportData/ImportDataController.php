<?php

namespace Glued\Controllers\ImportData;
use Glued\Controllers\Controller;
//use Glued\Classes\Permissions;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportDataController extends Controller
{
    // shows select of file and table(schema)
    public function importFormStep1($request, $response)
    {
        $file_options = '';
        
        $this->container->db->where("c_inherit_table", 't_helper');
        $this->container->db->where("c_inherit_object", 1);
        $this->container->db->orderBy("c_filename","asc");
        $items = $this->container->db->get('t_stor_links');
        if (count($items) > 0) {
            foreach ($items as $data) {
                $file_options .= '<option value="'.$data['c_uid'].'">'.$data['c_filename'].'</option>';
            }
        }
        
        return $this->container->view->render($response, 'importdata/step1.twig', array('file_options' => $file_options));
    }
    
    
    // show form for set column - path relations, spousti se jako prichod z post formulare
    public function importFormStep2($request, $response)
    {
        $submit_action = $request->getParam('submit_action');
        $file_id = $request->getParam('file_id');
        $schema_name = $request->getParam('schema_name');   // bez cesty a koncovky .json
        $layout = $request->getParam('layout'); // 1 - vlevo jsou sloupce, 2 - vlevo jsou paths
        
        $preset = false;
        $setting_id = 0;
        if ($submit_action == 'preset') {
            $setting_id = $request->getParam('setting_id');
            if ($setting_id > 0) {
                $preset = true;
                $this->container->db->where("c_uid", $setting_id);
                $item = $this->container->db->getOne('t_imported_settings');
                $preset_data = json_decode($item['c_setting'], true);
            }
        }
        
        // priprava presetu
        $settings_options = '<option value="0" '.($setting_id == 0?'selected':'').'>nothing selected</option>';
        $this->container->db->where("c_schema", $schema_name);
        $this->container->db->where("c_layout", $layout);
        $this->container->db->orderBy("c_name","asc");
        $items = $this->container->db->get('t_imported_settings');
        if (count($items) > 0) {
            foreach ($items as $data) {
                $settings_options .= '<option value="'.$data['c_uid'].'" '.($setting_id == $data['c_uid']?'selected':'').'>'.$data['c_name'].'</option>';
            }
        }
        
        
        // SCHEMA
        
        // v __DIR__ je hodnota aktualniho adresare, takze zde /var/www/html/glued/glued/Controllers/ImportData
        // schema celeho formulare
        $json_schema = file_get_contents(__DIR__.'/V1/jsonschemas/'.$schema_name.'.json');
        $json_array = json_decode($json_schema, true);
        $targety = $this->container->core->json_schema_targets($json_array['properties']);
        // $dump_data = print_r($json_array, true);
        
        // FILE
        
        // nacteme adresu souboru ze storu podle id
        $file_data = $this->container->stor->read_stor_file_info($file_id);
        
        // zpracujeme knihovnou phpspreadsheet
        $spreadsheet = IOFactory::load($file_data['fullpath']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        //$dump_data = print_r($sheetData, true);
        
        // LAYOUT
        
        // podle layoutu dame vlevo bud sloupce nebo targety
        
        if ($layout == 1) {
            // vytvorime radky tabulky kde bude vzdy sloupecek a select s target optiony
            $columns_output = '';
            foreach ($sheetData[1] as $col => $val) {
                $columns_output .= '
                    <tr>
                        <th scope="row">'.$col.'</th>
                        <td>'.$val.'</td>
                        <td><select name="target_'.$col.'">';
                if ($preset) {
                    if (empty($preset_data['target_'.$col])) { $columns_output .= '<option value="" selected>not set</option>'; }
                    else { $columns_output .= '<option value="">not set</option>'; }
                    foreach ($targety as $t) {
                        if (isset($preset_data['target_'.$col]) and $preset_data['target_'.$col] == $t) { $columns_output .= '<option selected>'.$t.'</option>'; }
                        else { $columns_output .= '<option>'.$t.'</option>'; }
                    }
                }
                else {
                    $columns_output .= '<option value="" selected>not set</option>';
                    foreach ($targety as $t) {
                        $columns_output .= '<option>'.$t.'</option>';
                    }
                }
                $columns_output .= '</select></td>
                        <td><select name="array1_'.$col.'">';
                if ($preset) {
                    $columns_output .= '<option value="0" '.($preset_data['array1_'.$col] == 0?'selected':'').'>1</option>';
                    $columns_output .= '<option value="1" '.($preset_data['array1_'.$col] == 1?'selected':'').'>2</option>';
                    $columns_output .= '<option value="2" '.($preset_data['array1_'.$col] == 2?'selected':'').'>3</option>';
                }
                else {
                    $columns_output .= '<option value="0" selected>1</option><option value="1">2</option><option value="2">3</option>';
                }
                $columns_output .= '</select></td>
                        <td><select name="array2_'.$col.'">';
                if ($preset) {
                    $columns_output .= '<option value="0" '.($preset_data['array2_'.$col] == 0?'selected':'').'>1</option>';
                    $columns_output .= '<option value="1" '.($preset_data['array2_'.$col] == 1?'selected':'').'>2</option>';
                    $columns_output .= '<option value="2" '.($preset_data['array2_'.$col] == 2?'selected':'').'>3</option>';
                }
                else {
                    $columns_output .= '<option value="0" selected>1</option><option value="1">2</option><option value="2">3</option>';
                }
                $columns_output .= '</select></td>
                    </tr>
                ';
            }
        }
        else if ($layout == 2) {
            $target_options = '<option value="" selected>not set</option>';
            foreach ($sheetData[1] as $col => $val) {
                $target_options .= '<option value="'.$col.'">'.$col.' '.$val.'</option>';
            }
            
            // vytvorime radky tabulky kde bude vzdy sloupecek a select s target optiony
            $columns_output = '';
            foreach ($targety as $ind => $t) {
                $pocet_arrays = substr_count($t, '[]');
                $columns_output .= '
                    <tr>
                        <th scope="row">['.$ind.']</th>
                        <td>'.$t.'</td>
                        <td><select name="target_'.$ind.'">';
                if ($preset) {
                    if (empty($preset_data['target_'.$ind])) { $columns_output .= '<option value="" selected>not set</option>'; }
                    else { $columns_output .= '<option value="">not set</option>'; }
                    foreach ($sheetData[1] as $col => $val) {
                        if (isset($preset_data['target_'.$ind]) and $preset_data['target_'.$ind] == $col) { $columns_output .= '<option value="'.$col.'" selected>'.$col.' '.$val.'</option>'; }
                        else { $columns_output .= '<option value="'.$col.'">'.$col.' '.$val.'</option>'; }
                    }
                }
                else {
                    $columns_output .= '<option value="" selected>not set</option>';
                    foreach ($sheetData[1] as $col => $val) {
                        $columns_output .= '<option value="'.$col.'">'.$col.' '.$val.'</option>';
                    }
                }
                $columns_output .= '</select></td>
                        <td>';
                if ($pocet_arrays > 0) {
                    $columns_output .= '<select name="array1_'.$ind.'">';
                    if ($preset) {
                        $columns_output .= '<option value="0" '.($preset_data['array1_'.$ind] == 0?'selected':'').'>1</option>';
                        $columns_output .= '<option value="1" '.($preset_data['array1_'.$ind] == 1?'selected':'').'>2</option>';
                        $columns_output .= '<option value="2" '.($preset_data['array1_'.$ind] == 2?'selected':'').'>3</option>';
                    }
                    else {
                        $columns_output .= '<option value="0" selected>1</option><option value="1">2</option><option value="2">3</option>';
                    }
                    $columns_output .= '</select>';
                }
                $columns_output .= '</td>
                        <td>';
                if ($pocet_arrays > 1) {
                    $columns_output .= '<select name="array2_'.$ind.'">';
                    if ($preset) {
                        $columns_output .= '<option value="0" '.($preset_data['array2_'.$ind] == 0?'selected':'').'>1</option>';
                        $columns_output .= '<option value="1" '.($preset_data['array2_'.$ind] == 1?'selected':'').'>2</option>';
                        $columns_output .= '<option value="2" '.($preset_data['array2_'.$ind] == 2?'selected':'').'>3</option>';
                    }
                    else {
                        $columns_output .= '<option value="0" selected>1</option><option value="1">2</option><option value="2">3</option>';
                    }
                    $columns_output .= '</select>';
                }
                $columns_output .= '</td>
                    </tr>
                ';
            }
            
        }
        
        // 'dump_data' => $dump_data
        return $this->container->view->render($response, 'importdata/step2.twig', array(
            'layout' => $layout,
            'file_id' => $file_id,
            'file_name' => $file_data['filename'],
            'settings_options' => $settings_options,
            'schema_name' => $schema_name,
            'columns_output' => $columns_output,
            'dump_data' => $dump_data
        ));
    }
    
    
    // zpracuje zdroj podle nastavenych pravidel ve formu 2, TODO jeste se musime rozhodnout, jestli to bude mit twig, nebo se to pak presmeruje treba zase na step 1 jen s nejakou hlaskou
    // data vlozi do tabulky t_imported_data
    public function importFormResult($request, $response)
    {
        $submit_action = $request->getParam('submit_action');
        $schema_name = $request->getParam('schema_name');   // bez cesty a koncovky .json, tady to ale asi bude nanic, leda pozdeji z toho urcit tabulku
        $layout = $request->getParam('layout'); // 1 - vlevo jsou sloupce, 2 - vlevo jsou paths
        
        // podle akce budeme bud ukladat nastaveni, nebo importovat
        if ($submit_action == 'save') {
            $setting_name = $request->getParam('setting_name');
            
            // vlozime do tabulky t_imported_settings pro vybrane schema a layout
            $setting = json_encode($_POST);
            $insert_array = array("c_schema" => $schema_name, "c_layout" => $layout, "c_name" => $setting_name, "c_setting" => $setting);
            $insert = $this->container->db->insert('t_imported_settings', $insert_array);
            
            $vystupni_retezec = 'new setting was saved';
        }
        else if ($submit_action == 'import') {
            $file_id = $request->getParam('file_id');
            
            // pripravime targety ze schema
            $json_schema = file_get_contents(__DIR__.'/V1/jsonschemas/'.$schema_name.'.json');
            $json_array = json_decode($json_schema, true);
            $targety = $this->container->core->json_schema_targets($json_array['properties']);
            
            
            // nacteme adresu souboru ze storu podle id
            $file_data = $this->container->stor->read_stor_file_info($file_id);
            
            // zpracujeme knihovnou phpspreadsheet
            $spreadsheet = IOFactory::load($file_data['fullpath']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            
            // resetneme tabulku
            $this->container->db->rawQuery('TRUNCATE TABLE t_imported_data ');
            
            // ted projdeme radky v souboru a kazdy vlozime podle nastavenych pravidel
            //$vystup_pole = array();
            $vlozeno = 0;
            foreach ($sheetData as $index => $data) {
                if ($index == 1) { continue; }  // preskocime prvni
                
                // zacneme tvorit pole json dat pro radek
                $json_pole = array();
                
                // LAYOUT zpracovani, jina interpretace dat
                
                if ($layout == 1) {
                    // u kazdeho sloupce se podivame jestli je prirazen a podle toho vytvorime cas json dat
                    foreach ($data as $col => $val) {
                        if (!empty($_POST['target_'.$col])) {
                            $casti_cesty = explode('.', $_POST['target_'.$col]);
                            $asociativni_zavorky = '';
                            $pozice_pole = 1;
                            foreach ($casti_cesty as $cast) {
                                if ($cast == '$') { continue; }
                                else if ($cast == '[]') {
                                    $index_pole = (int) $_POST['array'.$pozice_pole.'_'.$col];
                                    $asociativni_zavorky .= '['.$index_pole.']';  // vybrany index pole
                                    $pozice_pole++;
                                }
                                else {
                                    $asociativni_zavorky .= '["'.$cast.'"]';
                                }
                            }
                            // evalujeme
                            $evaluacni_retezec = "\$json_pole".$asociativni_zavorky."='".$val."';";
                            //$vystup_pole[] = $evaluacni_retezec;
                            eval($evaluacni_retezec);
                        }
                    }
                }
                else if ($layout == 2) {
                    // u kazdeho targetu se podivame jestli je prirazen a podle toho vytvorime cas json dat
                    foreach ($targety as $ind => $t) {
                        if (!empty($_POST['target_'.$ind])) {
                            $col = $_POST['target_'.$ind];  // pismeno sloupce a zaroven klic k datum
                            $casti_cesty = explode('.', $t);
                            $asociativni_zavorky = '';
                            $pozice_pole = 1;
                            foreach ($casti_cesty as $cast) {
                                if ($cast == '$') { continue; }
                                else if ($cast == '[]') {
                                    $index_pole = (int) $_POST['array'.$pozice_pole.'_'.$ind];
                                    $asociativni_zavorky .= '['.$index_pole.']';  // vybrany index pole
                                    $pozice_pole++;
                                }
                                else {
                                    $asociativni_zavorky .= '["'.$cast.'"]';
                                }
                            }
                            // evalujeme
                            $evaluacni_retezec = "\$json_pole".$asociativni_zavorky."='".$data[$col]."';";
                            //$vystup_pole[] = $evaluacni_retezec;
                            eval($evaluacni_retezec);
                        }
                    }
                }
                
                // vlozime do tabulky t_imported_data
                $json_data = json_encode($json_pole);
                $insert_array = array("c_data" => $json_data);
                $insert = $this->container->db->insert('t_imported_data', $insert_array);
                
                $vlozeno++;
                //$vystup_pole[] = $json_data;
            }
            
            $vystupni_retezec = 'inserted '.$vlozeno.' rows';
            
        }
        
        return $this->container->view->render($response, 'importdata/result.twig', array(
            'vystup' => $vystupni_retezec
        ));
    }
    
    
    
}
