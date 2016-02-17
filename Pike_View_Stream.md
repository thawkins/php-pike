# What is Pike\_View\_Stream? #

Security is becoming a topic more and more everyday. Hackers are getting more smarter and so applications need to improve their security. Since Javascript libraries like JQuery, ExtJS, Prototype, etc are beginning to get really hot and applications have more user interaction with ajax, UI like tools etc XSS has became one of the most problems from websites being hacked. XSS attacks can be a serious problem if a potential hacker can pass by server-side security checks when playing with ajax functions etc.

## How to protect? ##
Don't get to afraid yet! You can protect you application for 99,9% against all hacker attacks if you program and configurate everything nicely. The 0,1% is for the ultra geeks who there always will be and are able to hack every site. Since there is a very (I mean VERY) little change that caught you we don't concern about that.

One of the common topics is XSS attacks. This means by inputting javascript in some form which will save the input data to a database for instance and when displaying this data (and maybe on the screen of a other user/victim!) will it be executed and 'something' happends. This 'something' could have imagine consequentions you can imagine.

To protect this, is very simple. Just escape every output which is displayed on the webpage. Every variable value which is pulled from the database for example. If you do this correctly it will bring XSS danger to a very minimum!

## What is the benefit of Pike\_View\_Stream? ##
Yes, you got me there, what if I forget to escape? Because manually escape **every** variable which is displayed on the screen is quite a intensive job. This is where Pike\_view\_Stream gets cool.

Why is it so cool? Simply because it **automaticly** escapes every displayed variable using PHP's echo function (with and without shortcuts) on the screen. Yes youre right, every variable!

So actually when you have the streamwapper installed you do not need to modify any view.

## Freaky awesome! But what if I have an variable which need to be displayed raw? ##
If you have a variable which you trust and proberly needs to display HTML there is very simple approach to accomplish this:

```
<?=~$this->iReallyTrustMyVariable?>
```

That's all. Just add the tilde ~ and the job is done. This variable is left alone when parsing the view. But be carefull if there is user input in it.

## Okay, i'm convinced, how to install? ##
Installation is even easier. Just upload the Pike folder to your library in you application and ad the following lines to your application.ini:

```
pluginPaths.Pike_Application_Resource = "Pike/Application/Resource"
resources.stream.streamWrapper = "Pike_View_Stream"
autoloaderNamespaces[] = "Pike"
```

There are only two important things. The first is that you should aware that the second line from the configuration example above is added BEFORE you have something like: resources.view[.md](.md) =. The second thing is that, and it might sound a little weird that you should disable short\_open\_tag in your php.ini or virtualhost configuration.

This is a reported bug in the Zend Framework, if short\_open\_tag is set to "on" then no stream will be used at all and you cannot use the Pike\_View\_Stream. In order to make sure that Zend Framework will trigger his view stream (and look for Pike\_View\_stream) you should set this setting to Off.

This doesn't mean that you cannot use the short tag at all! Because of the Pike\_View\_Stream you can use it because it will replace every <?= or <? instance in your view script with <?php echo or <?php.

So we hope that this will help you to make a more safer application!