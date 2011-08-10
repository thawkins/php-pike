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

use DoctrineExtensions\Paginate\Paginate;

/**
 * Datasource for Doctrine queries and entities. You can use this datasource with
 * Pike_Grid which will both create all neccasary javascript and JSON for drawing
 * a grid with JQgrid.
 * 
 * Dependecies: JQuery, jqGrid, Doctrine, DoctrineExtensions, Zend Framework
 *
 */
class Pike_Grid_Datasource_Doctrine implements Pike_Grid_Datasource_Interface
{

    protected $data = array();
    protected $columns = array();
    protected $query;

    protected $params = array();
    protected $limitPerPage = 50;
    
    public function __construct(Doctrine\ORM\QueryBuilder $query)
    {
        $this->data = $query->getQuery()->getArrayResult();
        $this->query = $query->getQuery();

    }

    public function getColumns()
    {
        if (count($this->columns) == 0) {
            $names = array_keys($this->data[0]);
            $this->columns = array();

            foreach ($names as $name)
                $this->columns[$name] = array('name' => $name, 'label' => $name);
        }

        return $this->columns;
    }

    public function hasColumn($name)
    {
        return array_key_exists($name, $this->columns);
    }

    public function getDefaultSorting()
    {
        return null; //not yet supported.
    }

    public function setParameters(array $params) {
        $this->params = $params;
    }
    
    public function setResultsPerPage($num) {
        $this->limitPerPage = $num;
    }
    
    /**
     *
     * Returns a JSON string useable for JQuery Grid. This grids interprets this
     * data and is able to draw a grid.
     * 
     * @return string JSON data
     */
    public function getJSON()
    {        
        $offset = $this->limitPerPage * ($this->params['page'] - 1);

        $count = Paginate::getTotalQueryResults($this->query);
        $paginateQuery = Paginate::getPaginateQuery($this->query, $offset, $this->limitPerPage);        
        $result = $paginateQuery->getResult();
        
        $data = array();
        $data['page'] = (int)$this->params['page'];
        $data['total'] = ceil($count / $this->limitPerPage);
        $data['records'] = $count;
        $data['rows'] = array();
        
        foreach($result as $row) {
            $data['rows'][] = array('cell' => array_values($row));
        }
                
        return json_encode($data);
    }

}