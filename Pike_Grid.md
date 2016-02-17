# What is PiKe Grid? #

Good developers should never reinvent the wheel unless they can do it better. Pike\_Grid is a datagrid toolkit to generate datagrids out of the box. As you might already have noticed all of our libraries are optimized for Doctrine2 and Zend Framework.

As Pieter en I (Kees) developing software in Zend Framework we have mentioned that there aren't good datagrid classes for this framework. Many other developers create there own table helper and are reinventing the wheel. There are some grids for Zend Framework available but mostly only compatible for Zend\_Db.

We want to make all Zend Framework users which have their framework intergrated with Doctrine2 very happy and so we came with Pike\_Grid and it's cool!

# Okay, why is it so cool? #

As you have mentioned we believe in not to reinvent the wheel. Pike\_Grid is specialised on integrating Doctrine2, Zend Framework and jqGrid in one Datagrid library. The hardcore data work is done by Doctrine2 and passing it thru correct in Zend Framework and Jquery jqGrid makes it look nice based in your webapplication.

Benefits:

  * No dirty code but passing DQL queries into the datasource
  * Nice and clean object oriented API
  * You can customize your grid and make it nice with just JQuery
  * Works with Doctrine2!
  * Pike\_Grid does generating all the Javascript for you

# How-to use #

Pike\_Grid is still under development but using it will be something like this:

```
    public function indexAction()
    {
        $this->view->headTitle('Index pagina');

        /* @var $em Doctrine\ORM\EntityManager */
        $em = $this->_helper->em();
        
        $query = $em->createQuery('SELECT u.username, u.fullname, u.insertdate, ' . 
                'ug.name AS usergroup FROM BestBuy\Entity\User AS u JOIN u.usergroup AS ug');
                     
        $datasource = new Pike_Grid_Datasource_Doctrine($query);
        
        $grid = new Pike_Grid($datasource);
        $grid->setColumnName('username', 'Gebruikersnaam');
        $grid->setColumnName('fullname', 'Naam');
        $grid->setColumnName('insertdate', 'Toegevoegd op');
        $grid->setRowsPerPage(10);
      
        $this->view->grid = $grid;
        $this->view->headScript()->appendScript($grid->getJavascript(), 'text/javascript');

        if ($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            
            $datasource->setParameters($this->getRequest()->getPost());
            
            echo $datasource->getJSON();        
        }
```

# Status #

Pike\_Grid is not finished yet, but atleast it's working! Still some features have to be implemented

Work needs to be done:
  * Add support for passing EntityRepositories
  * More grid customalisation options (multiselect, editable, etc)
  * Possibillity to set cache lifetime of datasource data
  * Easier integration in a Zend Framework application
  * Dynamic field support
  * Integration of Smarty option (not sure)

So keep an eye on this or checkout the code and give it a try!