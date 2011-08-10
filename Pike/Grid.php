<?php
/**
 * Copyright (C) 2011 by Pieter Vogelaar (Platina Designs) and Kees Schepers (SkyConcepts)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 *
 * @version 1.0Beta
 * @author  Kees Schepers   <kees@skyconcepts.nl>
 * @author  Pieter Vogelaar <ps.vogelaar@platinadesigns.nl>
 */
class Pike_Grid {

    protected $id;
    protected $pagerid;
    protected $columns = array();

    /**
     *
     * @var Pike_Grid_Datasource_Interface
     */
    protected $datasource;
    protected $recordsPerPage = 50;

    protected $width = 'auto';
    protected $height = '100%';
    
    protected $url;
    
    protected $caption;
    /**
     * Pike_Grid needs to know the datasource in order to generate the initial column names etc.
     * 
     * @param Pike_Grid_Datasource_Interface $datasource 
     */
    public function __construct(Pike_Grid_Datasource_Interface $datasource) {
        $id = rand(0, 3000);

        $this->id = 'pgrid' . $id;
        $this->pagerid = 'pgrid' . $id . 'pager';

        $this->datasource = $datasource;
        $this->columns = $this->datasource->getColumns();
        
        $this->url = $_SERVER['REQUEST_URI'];
    }

    /**
     *
     * Replaces the datsource column name to a (nice) new name
     * 
     * @param type $original
     * @param type $new 
     */
    public function setColumnName($originalName, $newName) {
        if($this->datasource->hasColumn($originalName)) {            
            $this->columns[$originalName]['label'] = $newName;
        }
        
        return $this;
    }

    public function setRowsPerPage($amount) {
        $this->datasource->setResultsPerPage($amount);
        $this->recordsPerPage = $amount;
        
        return $this;
    }
    
    /**
     *
     * Set the URL where json data will be requested.
     * 
     * @param string $url 
     */
    public function setUrl($url) {
        $this->url = $url;
        
        return $this;
    }

    public function setWidth($width = 'auto') {
        if(!is_numeric($width)) {
            $width = 'auto';
        }

        return $this;
    }

    public function getHTML() {
        return '<table id="' . $this->id . '"></table><div id="' . $this->pagerid . '"></div>';
    }

    /**
     * Returns a jqGrid declaration with all neccasary settings.
     */
    public function getJavascript() {        
        $settings = array(
            'url'           => $this->url,
            'datatype'      => 'json',
            'mtype'         => 'post',
            'rowNum'        => $this->recordsPerPage,
            'autoWidth'     => true,
            'pager'         => $this->pagerid,
            'height'        => $this->height,            
            'viewrecords'   => true,            
            'colModel'      => array_values($this->columns),
        );

        foreach ($this->columns as $column) {
            $settings['colNames'][] = $column['label'];
        }
        
        if (!is_null($defaultSorting = $this->datasource->getDefaultSorting())) {
            $settings['sortname'] = $defaultSorting['index'];
            $settings['sortorder'] = $defaultSorting['direction'];
        }

        if (!is_null($this->caption)) {
            $settings['caption'] = $this->caption;
        }
        
        switch($this->width) {
            case 'auto' :
                $settings['autowidth'] = true;
                break;
            default : //width in pixels?
                $settings['width'] = (int)$this->width;
                break;
        }

        $output = '$("#' . $this->id . '").jqGrid(' . json_encode($settings) . ');';
        
        return $output;
    }

}