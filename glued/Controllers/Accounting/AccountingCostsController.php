<?php
namespace Glued\Controllers\Accounting;

use Glued\Controllers\Controller;

class AccountingCostsController extends Controller
{

    // shows basic page with all costs
    public function getCosts($request, $response)
    {
        $costs_output = '';
        $this->container->db->orderBy("c_uid","asc");
        $bills = $this->container->db->get('accounting_accepted');
        if (count($bills) > 0) {
            foreach ($bills as $data) {
                $billdata = json_decode($data['c_data'], true);
                $costs_output .= '<div>ID: '.$data['c_uid'].', bill number: '.$billdata['bill-nr'].' <a href="editcost/'.$data['c_uid'].'">edit</a></div>';
            }
        }
        else {
            $costs_output = 'zatim zadne nejsou vlozeny';
        }
        
        return $this->container->view->render($response, 'accounting/costs.twig', array('costs_output' => $costs_output));
    }
    
    // show form for add new cost
    public function addCostForm($request, $response)
    {
        $form_output = 'zde je formular';
        $json_uischema_output = '
{
    "date-plneni": {
      "ui:widget": "alt-date"
    },
    "poznamka": {
      "ui:widget": "textarea"
    },
    "prirazeni": {
        "items": {
          "poznamka": {
            "ui:widget": "textarea"
          }
        }
    }
}
        ';
        //     "required" : [ "wovat", "vat" ],
        
        $json_schema_output = '
{
    "title": "New bill form",
    "type": "object",
    "properties":{
        "files": {
          "type": "array",
          "title": "Multiple files",
          "items": {
            "type": "string",
            "format": "data-url"
          }
        },
        "dodavatel": {
            "title": "Dodavatel",
            "type": "object",
            "properties": {
                "nazev": {
                    "type": "string",
                    "title": "Název",
                },
                "ico": {
                    "type": "string",
                    "title": "IČ",
                },
                "adresa": {
                    "type": "string",
                    "title": "Adresa",
                }
            }
        },
        "date-plneni": {
          "type": "string",
          "format": "date",
          "title": "Datum zdanitelného plnění",
        },
        "bill-nr": {
          "type": "string",
          "title": "Číslo účtenky"
        },
        "generated-nr": {
          "type": "string",
          "title": "Generované číslo"
        },
        "prirazeni": {
          "type": "array",
          "title": "Přiřazení k zakázkám",
          "items": {
            "type": "object",
            "properties": {
                "zakazka-nr": {
                    "type": "string",
                    "title": "Číslo zakázky",
                },
                "sum": {
                    "type": "string",
                    "title": "Částka k zakázce",
                },
                "poznamka": {
                    "type": "string",
                    "title": "Poznámka k zakázce",
                }
            }
          }
        },
        "poznamka": {
          "type": "string",
          "title": "Poznámka"
        },
        "wovat": {
          "type": "string",
          "title": "Cena bez DPH"
        },
        "vat": {
          "type": "string",
          "title": "Cena s DPH"
        }
        
    }
}
        ';
        
        // zakladni data, jedna prazdna polozka arraye "prirazeni"
        $json_formdata_output = '
{
  "prirazeni": [
    {
      "zakazka-nr": "",
      "sum": "",
      "poznamka": ""
    }
  ]
}
        ';
        
        
        return $this->container->view->render($response, 'accounting/addcost.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output
        ));
    }
    
    // show form for edit existing cost
    public function editCostForm($request, $response, $args)
    {
        
        $this->container->db->where("c_uid", $args['id']);
        $data = $this->container->db->getOne('accounting_accepted');
        
        
        $form_output = 'zde je formular';
        $json_uischema_output = '
{
    "date-plneni": {
      "ui:widget": "alt-date"
    },
    "poznamka": {
      "ui:widget": "textarea"
    },
    "prirazeni": {
        "items": {
          "poznamka": {
            "ui:widget": "textarea"
          }
        }
    }
}
        ';
        //     "required" : [ "wovat", "vat" ],
        
        $json_schema_output = '
{
    "title": "Edit bill form",
    "type": "object",
    "properties":{
        "files": {
          "type": "array",
          "title": "Multiple files",
          "items": {
            "type": "string",
            "format": "data-url"
          }
        },
        "dodavatel": {
            "title": "Dodavatel",
            "type": "object",
            "properties": {
                "nazev": {
                    "type": "string",
                    "title": "Název",
                },
                "ico": {
                    "type": "string",
                    "title": "IČ",
                },
                "adresa": {
                    "type": "string",
                    "title": "Adresa",
                }
            }
        },
        "date-plneni": {
          "type": "string",
          "format": "date",
          "title": "Datum zdanitelného plnění",
        },
        "bill-nr": {
          "type": "string",
          "title": "Číslo účtenky"
        },
        "generated-nr": {
          "type": "string",
          "title": "Generované číslo"
        },
        "prirazeni": {
          "type": "array",
          "title": "Přiřazení k zakázkám",
          "items": {
            "type": "object",
            "properties": {
                "zakazka-nr": {
                    "type": "string",
                    "title": "Číslo zakázky",
                },
                "sum": {
                    "type": "string",
                    "title": "Částka k zakázce",
                },
                "poznamka": {
                    "type": "string",
                    "title": "Poznámka k zakázce",
                }
            }
          }
        },
        "poznamka": {
          "type": "string",
          "title": "Poznámka"
        },
        "wovat": {
          "type": "string",
          "title": "Cena bez DPH"
        },
        "vat": {
          "type": "string",
          "title": "Cena s DPH"
        }
        
    }
}
        ';
        
        // zakladni data, jedna prazdna polozka arraye "prirazeni"
        $json_formdata_output = $data['c_data'];
        
        
        return $this->container->view->render($response, 'accounting/editcost.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'cost_id' => $args['id']
        ));
    }
    
    
    // api for add new cost (parametr args neni potreba, post promenna bude v request)
    public function insertCostApi($request, $response)
    {
        
        $senddata = $request->getParam('billdata');
        
        $data = Array ("c_owner" => 1, "c_group" => 1, "c_unixperms" => 500,
                       "c_data" => $senddata
        );
        
        $insert = $this->container->db->insert('accounting_accepted', $data);
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    
    // api for edit (parametr args ma, jedeme putem)
    public function editCostApi($request, $response, $args)
    {
        
        $senddata = $request->getParam('billdata');
        
        $this->container->db->where('c_uid', $args['id']);
        $update = $this->container->db->update('accounting_accepted', Array ( 'c_data' => $senddata ));
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    
}
