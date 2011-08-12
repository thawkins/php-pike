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
    
    public function __construct($source)
    {
        switch($source) {
            case ($source instanceof Doctrine\ORM\QueryBuilder) :
                $this->query = $source->getQuery();
                break;
            case ($source instanceof Doctrine\ORM\Query) :
                 $this->query = $source;
                break;
            case ($source instanceof \Doctrine\ORM\EntityRepository) :
                $this->query = $source->createQueryBuilder('al')->getQuery();
                break;
        }
                
        /**
         * Data is used for the column names so using a metamapper would be better.
         */
        $query = clone $this->query;        
        $this->data = $query->getArrayResult();

    }

    /**
     *
     * Looks up in the AST what select expression we use and analyses which
     * fields are used. This is passed thru the datagrid for displaying fieldnames.
     * The array returned is a jqGrid compattible array.
     * 
     * @return array
     */
    public function getColumns()
    {
        $selectClause = $this->query->getAST()->selectClause;
        if(count($selectClause->selectExpressions) == 0) {
            throw new Pike_Exception('The grid query should contain at least one column, none found.');
        }
        
        $this->columns = array();
                
        /* @var $selExpr Doctrine\ORM\Query\AST\SelectExpression */
        foreach($selectClause->selectExpressions as $selExpr) {
            
            /* @var $expr Doctrine\ORM\Query\AST\PathExpression */
            $expr = $selExpr->expression;
            
            $alias = $expr->identificationVariable;
            $name = $expr->field;
            $label = ($selExpr->fieldIdentificationVariable === null) ? $name : $selExpr->fieldIdentificationVariable;
            
            $this->columns[$name] = array(
                'name' => $name, 
                'label' => $label, 
                'index' => (strlen($alias) > 0 ? ($alias . '.') : '') . $name
            );
            
        }

        return $this->columns;
    }

    /**
     *
     * Test if the grid/source has given columnname
     * 
     * @param string $name column name
     * @return boolean
     */
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
        $hints = array();

        $count = Pike_Grid_Datasource_Doctrine_Paginate::getTotalQueryResults($this->query);        
        
        /**
         * Sorting if defined
         */
        if(array_key_exists('sidx', $this->params) && strlen($this->params['sidx']) > 0) {
            $columns = $this->columns;
            $sidx = $this->params['sidx'];
            $sord = (in_array(strtoupper($this->params['sord']), array('ASC','DESC')) ? strtoupper($this->params['sord']) : 'ASC');
            
            //test if searchindex really is defined. (security)
            $hasSidx = function() use($columns, $sidx) {
              foreach($columns as $column) {
                  if($columns['index'] == $sidx) return true;
              }
              
              return false;
            };
            
            if($hasSidx) {
                $hints[\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS] = array('Pike_Grid_Datasource_Doctrine_OrderByWalker');
                $hints['sidx'] =  $sidx;
                $hints['sord'] = $sord;
            }
        }        
        
        $paginateQuery = Pike_Grid_Datasource_Doctrine_Paginate::getPaginateQuery(
                $this->query,
                $offset,
                $this->limitPerPage,
                $hints
        );
        
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