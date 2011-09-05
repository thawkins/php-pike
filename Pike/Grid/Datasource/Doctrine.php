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
 * Dependecies: jqGrid, Doctrine, Zend Framework
 *
 */
class Pike_Grid_Datasource_Doctrine implements Pike_Grid_Datasource_Interface
{
    /**
     *
     * The columns 'container'
     *
     * @var Pike_Grid_Datasource_Columns
     */
    public $columns;

    /**
     * Array container where the actual grid data will be loaded in.
     *
     * @var array
     */
    protected $_data = array();

    protected $_query;

    protected $_params = array();
    protected $_limitPerPage = 50;
    
    /**
     * When this column is set it tells jqGrid how to identify each row
     * 
     * @var array
     */
    protected $_identifierColumn;

    public function __construct($source)
    {
        switch($source) {
            case ($source instanceof Doctrine\ORM\QueryBuilder) :
                $this->_query = $source->getQuery();
                break;
            case ($source instanceof Doctrine\ORM\Query) :
                 $this->_query = $source;
                break;
            case ($source instanceof \Doctrine\ORM\EntityRepository) :
                $this->_query = $source->createQueryBuilder('al')->getQuery();
                break;
            default :
                throw new Pike_Exception('Unknown source given, source must either be an entity, query or querybuilder object.');
                break;
        }

        $this->setColumns();

    }
    /**
     *
     * Looks up in the AST what select expression we use and analyses which
     * fields are used. This is passed thru the datagrid for displaying fieldnames.
     *
     *
     * @return array
     */
    private function setColumns()
    {
        $this->columns = new Pike_Grid_Datasource_Columns();

        $selectClause = $this->_query->getAST()->selectClause;
        if(count($selectClause->selectExpressions) == 0) {
            throw new Pike_Exception('The grid query should contain at least one column, none found.');
        }

        /* @var $selExpr Doctrine\ORM\Query\AST\SelectExpression */
        foreach($selectClause->selectExpressions as $selExpr) {

            /**
             * Some magic needs to happen here. If $selExpr isn't a instanceof
             * Doctrine\ORM\Query\AST\PathExpression then it might be a custom
             * expression like a vendor specific function. We could use the 
             * $selExpr->fieldIdentificationVariable member which is the alias
             * given to the special expression but sorting at this field
             * could cause strange effects.
             * 
             * For instance: SELECT DATE_FORMAT(datefield,"%Y") AS alias FROM sometable
             * 
             * When you say: ORDER BY alias DESC
             * 
             * You could get other results when you do:
             * 
             * ORDER BY sometable.datefield DESC
             * 
             * My idea is to rely on the alias field by default because it would 
             * suite for most queries. If someone would like to retrieve this expression
             * specific info they can add a field filter. $ds->addFieldFilter(new DateFormat_Field_Filter(), 'expression classname');
             * 
             * And info of the field would be extracted from this class, something like that.
             */
            
            $expr = $selExpr->expression;
            
            /* @var $expr Doctrine\ORM\Query\AST\PathExpression */
            if($expr instanceof Doctrine\ORM\Query\AST\PathExpression) {
                $alias = $expr->identificationVariable;
                $name = ($selExpr->fieldIdentificationVariable === null) ? $expr->field : $selExpr->fieldIdentificationVariable;
                $label = ($selExpr->fieldIdentificationVariable === null) ? $name : $selExpr->fieldIdentificationVariable;
                $index = (strlen($alias) > 0 ? ($alias . '.') : '') . $name;
            } else {
                $name = $selExpr->fieldIdentificationVariable;
                $label = $name;
                $index = null;
            }
            $this->columns->add($name, $label, $index);
        }
        
    }
    
    public function setIdentifierColumn($column) {
        if(isset($this->columns[$column])) {
            $this->_identifierColumn = $this->columns[$column];
        } else {
            throw new Pike_Exception('Cannot set identifier to a unknown column ('.$column.')');
        }
    }

    /**
     *
     * Look if there is default sorting defined in the original query by asking the AST. Defining
     * default sorting is done outside the datasource where query or querybuilder object is defined.
     *
     * @return array
     */
    public function getDefaultSorting()
    {
        if(null !== $this->_query->getAST()->orderByClause) {
           //support for 1 field only
            $orderByClause = $this->_query->getAST()->orderByClause;

            /* @var $orderByItem Doctrine\ORM\Query\AST\OrderByItem */
            $orderByItem = $orderByClause->orderByItems[0];

            if($orderByItem->expression instanceof \Doctrine\ORM\Query\AST\PathExpression) {
                $alias = $orderByItem->expression->identificationVariable;
                $field = $orderByItem->expression->field;

                $data['index'] = (strlen($alias) > 0 ? $alias . '.' : '') . $field;
                $data['direction'] = $orderByItem->type;

                return $data;
            }
        }

        return null;
    }

    /**
     *
     * Set the parameters which proberly come from jquery.
     *
     * @param array $params
     */
    public function setParameters(array $params) {
        $this->_params = $params;
    }

    public function setResultsPerPage($num) {
        $this->_limitPerPage = (int)$num;
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
        $offset = $this->_limitPerPage * ($this->_params['page'] - 1);
        $hints = array();

        $count = Pike_Grid_Datasource_Doctrine_Paginate::getTotalQueryResults($this->_query);

        /**
         * Sorting if defined
         */
        if(array_key_exists('sidx', $this->_params) && strlen($this->_params['sidx']) > 0) {
            $columns = $this->columns->getColumns();
            $sidx = $this->_params['sidx'];
            $sord = (in_array(strtoupper($this->_params['sord']), array('ASC','DESC')) ? strtoupper($this->_params['sord']) : 'ASC');

            //test if searchindex really is defined. (security)
            $hasSidx = function() use($columns, $sidx) {
              foreach($columns as $column) {
                  if(!isset($column['index'])) return false;

                  if($sidx == $column['index']) return true;
              }

              return false;
            };
            $hasSidx = $hasSidx();

            if($hasSidx == false) {
                if(isset($columns[$sidx]['index'])) {
                    $hasSidx = true;
                    $sidx = $columns[$sidx]['index'];
                }
            }

            if($hasSidx) {
                $hints[\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS] = array('Pike_Grid_Datasource_Doctrine_OrderByWalker');
                $hints['sidx'] =  $sidx;
                $hints['sord'] = $sord;
            }
        }

        $paginateQuery = Pike_Grid_Datasource_Doctrine_Paginate::getPaginateQuery(
                $this->_query,
                $offset,
                $this->_limitPerPage,
                $hints
        );

        $result = $paginateQuery->getResult();

        $this->_data = array();
        $this->_data['page'] = (int)$this->_params['page'];
        $this->_data['total'] = ceil($count / $this->_limitPerPage);
        $this->_data['records'] = $count;
        $this->_data['rows'] = array();

        foreach($result as $row) {
            foreach($this->columns as $index=>$column) {
                if(is_callable($column['data'])) {
                    $row[$index] = $column['data']($row);
                } else {
                    throw new Pike_Exception('Could not draw data for column ('.$index.')');
                }
            }
            
            /**
             * Sort the row data specified by the column positions
             */
            $columns = $this->columns;
            
            uksort($row, function($a, $b) use ($columns) {
                if($columns[$a]['position'] > $columns[$b]['position']) {
                    return 1;
                } elseif($columns[$a]['position'] < $columns[$b]['position']) {
                    return -1;
                } else {
                    return 0;
                }
            });
            
            $record = array();
            $record['cell'] = array_values($row);
            
            if(null !== $this->_identifierColumn) {                
                $record['id'] = $this->_identifierColumn['data']($row);
            }
            
            $this->_data['rows'][] = $record;
        }

        return json_encode($this->_data);
    }

}