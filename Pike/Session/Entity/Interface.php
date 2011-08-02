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
 * This interface should be implemented by the session entity in your project. The session
 * entity is where all session data will be stored in (proberly) in a database. Implementing
 * these function will cause that the Pike_Session_SaveHandler_Doctrine knows how to talk with
 * Doctrine.
 * 
 * Session entity interface
 */
interface Pike_Session_Entity_Interface
{

  /**
   * Function which should retrieve the serialized session data. Do not serialize yourself!
   * This is done by the PHP session handler itself.
   * 
   * @return string
   */
  public function getData();

  /**
   * The function where the savehandler can set the serialized data to
   * 
   * @param string $data
   */
  public function setdata($data);

  /**
   * Function where the savehandler can set the last modified data thru. 
   * 
   * @param DateTime $date
   */
  public function setModified(DateTime $date);

  /**
   * Function to retrieve the last date modified
   * 
   * @return DateTime|String
   */
  public function getModified();

  /**
   * Retrieve the fieldname of the corresponding field where the modificationdate
   * of the session is stored. Is used for the garbage collector.
   */
  public static function getModifiedFieldName();

  /**
   * Function to set the session id
   * 
   * @param string $id
   */
  public function setId($id);
}