<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author kees
 */
interface Pike_Grid_Datasource_Interface {
    /**
     * Gives back an array with column information as name, datatype etc.
     * 
     * Should return a numeric array like:
     * array(
     *  'columnName' => 
     *      array('label' => 'userfriendlyname', 'index' => 'databasefieldname', 'sorttype' => 'datatype')
     * )
     * 
     * @return array
     */
    public function getColumns();

    public function hasColumn($name);
    
    public function getDefaultSorting();
}
?>
