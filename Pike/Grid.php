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
 * Pike_Grid is the front class. It wants a datasource passed thru the constructor
 * and generates Javascript and HTML for rendering the grid. With a AJAX POST call
 * the data is retrieved.
 */
class Pike_Grid
{

    protected $id;
    protected $pagerid;

    /**
     *
     * @var Pike_Grid_Datasource_Interface
     */
    protected $datasource;

    /**
     * Amount of rows per 'page' default is 50
     *
     * @var integer
     */
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
    public function __construct(Pike_Grid_Datasource_Interface $datasource, array $options = array())
    {
        $id = rand(0, 3000);

        $this->id = 'pgrid' . $id;
        $this->pagerid = 'pgrid' . $id . 'pager';

        $this->datasource = $datasource;

        $this->url = $_SERVER['REQUEST_URI'];
    }

    public function setRowsPerPage($amount)
    {
        $this->datasource->setResultsPerPage($amount);
        $this->recordsPerPage = $amount;

        return $this;
    }

    public function setCaption($caption)
    {
        $this->caption = $caption;

        return $this;
    }

    public function addColumn($name, $data, $label = null, $sidx = null, $position = null) {
        $this->datasource->columns->add($name, $label, $sidx, $position);

        //do something with data

        return $this;
    }

    public function setColumnAttribute($name, $attribute, $value) {
        $this->datasource->columns[$name][$attribute] = $value;

        return $this;
    }

    /**
     *
     * Set the URL where json data will be requested.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function setWidth($width = 'auto')
    {
        if (!is_numeric($width)) {
            $width = 'auto';
        }

        return $this;
    }

    public function getHTML()
    {
        return '<table id="' . $this->id . '"></table><div id="' . $this->pagerid . '"></div>';
    }

    /**
     * Returns a jqGrid declaration with all neccasary settings.
     */
    public function getJavascript()
    {
        $settings = array(
            'url' => $this->url,
            'datatype' => 'json',
            'mtype' => 'post',
            'rowNum' => $this->recordsPerPage,
            'autoWidth' => true,
            'pager' => $this->pagerid,
            'height' => $this->height,
            'viewrecords' => true,
            'colModel' => array_values($this->datasource->columns->getColumns()),
        );

        foreach ($this->datasource->columns as $column) {
            $settings['colNames'][] = $column['label'];
        }

        if (!is_null($defaultSorting = $this->datasource->getDefaultSorting())) {
            $settings['sortname'] = $defaultSorting['index'];
            $settings['sortorder'] = strtolower($defaultSorting['direction']);
        }

        if (!is_null($this->caption)) {
            $settings['caption'] = $this->caption;
        }

        switch ($this->width) {
            case 'auto' :
                $settings['autowidth'] = true;
                break;
            default : //width in pixels?
                $settings['width'] = (int) $this->width;
                break;
        }

        $json = json_encode($settings);

        $output = 'var lastsel;' . PHP_EOL;
        $output .= '$("#' . $this->id . '").jqGrid(' . $json . ');';

        return $output;
    }

}