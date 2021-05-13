<?php
namespace App\HTML;

use Phalcon\Di\Injectable;


class HelperPlugin extends Injectable
{

    public static function InfoBox($content,$value,$bg='bg-warning',$iconclass='fas fa-sign-out-alt'){
        $html='
        <div class="col-12 col-sm-6 col-md-2">
            <div class="info-box">
                <span class="info-box-icon '.$bg.' elevation-1"><i class="'.$iconclass.'"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">'.$content.'</span>
                    <span class="info-box-number">'.$value.'</span>
                </div>
            </div>
        </div>';
        return $html;
    }

    public static function ArrayToTable($array): string {
        $html='<table border="1">';
        $i=0;
        foreach ($array as $row){
            $html.='<tr>';
            $html.='<td>'.$i++.'</td>';
            if (is_array($row)){
                foreach ($row as $col)
                    $html.='<td>'.$col.'</td>';
            }
            else {
                $html .= '<td>' . $row . '</td>';
                $html .= '<td>' . gettype($row) . '</td>';
            }

            $html.='</tr>';
        }
        $html.='</table>';
        return $html;
    }

}
