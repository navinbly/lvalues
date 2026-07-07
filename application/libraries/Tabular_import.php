<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tabular_import
{
    public function rows($path, $extension)
    {
        return strtolower($extension) === 'xlsx' ? $this->xlsx($path) : $this->csv($path);
    }

    private function csv($path)
    {
        $handle=fopen($path,'r'); if(!$handle)return [];
        $rows=[]; while(($row=fgetcsv($handle))!==false)$rows[]=$row; fclose($handle); return $rows;
    }

    private function xlsx($path)
    {
        if(!class_exists('ZipArchive'))throw new RuntimeException('ZIP support is required for Excel import.');
        $zip=new ZipArchive(); if($zip->open($path)!==true)throw new RuntimeException('Excel file is unreadable.');
        $shared=[]; $sharedXml=$zip->getFromName('xl/sharedStrings.xml');
        if($sharedXml){$xml=simplexml_load_string($sharedXml);foreach($xml->si as $item){$parts=[];if(isset($item->t))$parts[]=(string)$item->t;foreach($item->r as $run)$parts[]=(string)$run->t;$shared[]=implode('',$parts);}}
        $sheet=$zip->getFromName('xl/worksheets/sheet1.xml');$zip->close();if(!$sheet)return [];
        $xml=simplexml_load_string($sheet);$rows=[];
        foreach($xml->sheetData->row as $row){$out=[];foreach($row->c as $cell){$ref=(string)$cell['r'];preg_match('/^[A-Z]+/',$ref,$match);$index=$this->column_index($match[0]??'A');$value=(string)$cell->v;if((string)$cell['t']==='s')$value=$shared[(int)$value]??'';elseif((string)$cell['t']==='inlineStr')$value=(string)$cell->is->t;$out[$index]=$value;}if($out){$max=max(array_keys($out));$rows[]=array_replace(array_fill(0,$max+1,''),$out);}}
        return $rows;
    }

    private function column_index($letters)
    {
        $index=0;foreach(str_split($letters) as $letter)$index=$index*26+(ord($letter)-64);return $index-1;
    }
}
