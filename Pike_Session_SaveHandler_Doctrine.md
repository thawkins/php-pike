# What can I do with Pike\_Session\_SaveHandler\_Doctrine? #

Normally PHP sessions are stored as files in your linux or Windows file system. For a lot of projects this is fine. You can modify the session save\_path to seperate the session files for proberly security reasons.

With bigger our complex projects where things as load balancing are neccasary saving the session is not that simple anymore. You could use memcache offcourse but a other approach is to save your sessions in the database. This is were Pike\_Session\_SaveHandler\_Doctrine should take your intrest.

Are you like us using Zend Framework and Doctrine 2 integrated together? And do you want to store your sessions in the database without using the Zend\_Session\_SaveHandler\_dbTable class which requires extra seperate configuration? Then take a look at this!

## What do you need to do? ##
To accomplish this integration with Zend Session and Doctrine, you should follow these steps:

## Step 1. Integrate de PiKe library ##
At the moment when writing this article the Pike\_Session\_SaveHandler\_Doctrine for Doctrine 2 is the only component that is available in the PiKe library. A lot of other components will be added, but most of them can be used individually. In this case it's easiest to download the full PiKe library and put it in the library folder of your Zend Framework application. To make sure that the PiKe library can be found with the Zend auto loader, add the following:

```
autoloadNamespaces[] = "Pike"
```

## Step 2. Creating an entity for Session SaveHandler ##
Because Pike\_Session\_SaveHandler\_Doctrine uses Doctrine 2 entities, you must create an entity that implements the Pike\_Session\_Entity\_Interface. This way Pike\_Session\_SaveHandler\_Doctrine know how to communicate with it and knows how to put in the database and retrieve from it. If you don't have a session table in your database already you may use our session entity.

```
namespace My\Entity;
 
require_once(__DIR__ . '/BasicEntity.php');
 
/**
 * Session
 *
 * @Table(name="session")
 * @Entity
 */
class Session implements \Pike_Session_Entity_Interface
{
 
  /**
   * @var string $session_id
   *
   * @Column(name="session_id", type="string", nullable=false)
   * @Id
   */
  protected $session_id;
 
  /**
   * @var string $session_data
   *
   * @Column(name="session_data", type="text", nullable=false)
   */
  protected $session_data;
 
  /**
   * @var string $session_expire
   *
   * @Column(name="session_expire", type="datetime", nullable=false)
   */
  protected $session_expire;
 
  public function getData()
  {
    return $this->session_data;
  }
 
  public function setdata($data)
  {
    $this->session_data = $data;
  }
 
  public function setModified(\DateTime $date)
  {
    $this->session_expire = $date;
  }
 
  public function getModified()
  {
    return $this->session_expire;
  }
 
  public static function getModifiedFieldName()
  {
    return 'session_expire';
  }
 
  public function setId($id)
  {
    $this->session_id = $id;
  }
 
}
```

There are several fields that can be modified as long as you make sure that the implemented methods return the right data. If this is not the case, strange things can happen. However, the example above will most of the time be sufficient.

## Step 3. Configure Zend\_Session in application.ini ##
The next step is to configure Zend\_Session in yout application.ini so that it will work with Pike\_Session\_SaveHandler\_Doctrine. This is very easy and only requires the addition of the following two lines:

```
resources.session.saveHandler.class = "Pike_Session_SaveHandler_Doctrine"

resources.session.saveHandler.options.entityName = "My\Entity\Session"
```

This should be sufficient to work. Replace "My" with the namespace you put your Doctrine entities in and check you session entity name. If this isn't correct, a lot of errors will occur.

## Step 4. Your bootstrap modification ##
A small modification in your bootstrap is also required. You must create an application resource for this to make optionally portable for other projects. However the following change is perfectly fine already:

```
...
protected function _initApplicationSession() 
{
    $this->bootstrap('doctrine');
    $this->bootstrap('session');
 
    $em = $this->getResource('Doctrine')->getEntityManager();
 
    Pike_Session_SaveHandler_Doctrine::setEntitityManager($em);
 
    Zend_Session::start();     
}
...
```

In the first place this code makes sure that Doctrine and Zend\_Session are bootstrapped as application resources. If you use Bisna you'll bootstrap the Doctrine resource. This resource will provide you with the class Bisa\Application\Container\DoctrineContainer that contains the getEntityManager method. This method is required for the entity manager retrieval that will be used in Pike\_Session\_SaveHandler\_Doctrine. Ofcourse you're totally free to retrieve the entity manager in every way you like. Please note that it's very important that this is done BEFORE Zend\_Session::start() is called.

Your sessions will be saved into the database!

There you are! You have a nice implemented way and you don't need to throw Doctrine out of the window. Cheers! The class Pike\_Session\_SaveHandler\_Doctrine is not very large and I expect (and hope not) very much bugs.

If you have any questions, issues or bugs, please report them at:
http://code.google.com/p/php-pike

The dutch version of this article is available at [Kees Schepers' blog](http://www.keesschepers.nl/2011/08/02/zend-session-doctrine-2/)