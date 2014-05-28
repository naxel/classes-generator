classes-generator
=================

Generate classes from json

Run from console:

```bash
php classes-generator.php --source=src --destination=classes --namespace=Classes --rootClass=SuperClass -r
```

Run manualy:

```php
$params = array(
     'sourceDir' => 'src',
     'destinationDir' => 'classes',
     'namespace' => 'Classes',
     'rootClassName' => 'SuperClass',
     'showRequires' => true,
);
 
$classesGenerator = new ClassesGenerator\Generator($params);
$classesGenerator->generate();
```
