<?php
namespace ClassesGenerator;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\PropertyGenerator;

class Generator
{
    public $sourceDir = '.';

    public $destinationDir = '.';

    public $namespace = null;

    public $rootClassName = null;

    public $showRequires = false;

    public $mappingClassesPropertyName = 'mappingClasses';

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        if (isset($params['sourceDir']) && $params['sourceDir']) {
            $this->sourceDir = $params['sourceDir'];
        }
        if (isset($params['destinationDir']) && $params['destinationDir']) {
            $this->destinationDir = $params['destinationDir'];
        }
        if (isset($params['namespace']) && $params['namespace']) {
            $this->namespace = $params['namespace'];
        }
        if (isset($params['rootClassName']) && $params['rootClassName']) {
            $this->rootClassName = $params['rootClassName'];
        }
        if (isset($params['showRequires']) && $params['showRequires']) {
            $this->showRequires = $params['showRequires'];
        }
    }

    /**
     * Generate classes from json
     */
    public function generate()
    {
        $files = glob(realpath($this->sourceDir) . '/*.json');
        $classes = array();

        if ($this->rootClassName) {
            $path = $this->generateRootClass();
            $classes[$path] = $path;
        }

        foreach ($files as $file) {
            $path = $this->generateClass($file);
            $classes[$path] = $path;
        }

        foreach ($classes as $classPath) {
            if ($this->showRequires) {
                echo "require_once '" . $classPath . "';\n";
            } else {
                echo $classPath . "\n";
            }
        }
    }

    /**
     * @return string
     */
    public function generateRootClass()
    {
        $class = new ClassGenerator();
        $class->setAbstract(true);
        $class->setName($this->rootClassName);
        $class->setNamespaceName($this->namespace);
        $class->addMethods(
            array(
                MethodGenerator::fromArray(
                    array(
                        'name' => '__construct',
                        'parameters' => array('data = array()'),
                        'body' => '$this->fromArray($data);',
                        'docblock' => DocBlockGenerator::fromArray(
                            array(
                                'shortDescription' => 'Constructor',
                                'longDescription' => null,
                                'tags' => array(
                                    new Tag\ParamTag('data', 'array'),
                                )
                            )
                        ),
                    )
                ),
                MethodGenerator::fromArray(
                    array(
                        'name' => 'fromArray',
                        'parameters' => array('data'),
                        'body' => '
foreach ($data as $key => $val) {

    if (is_int($key)) {
        if (method_exists($this, "add")) {
            $this->add($val);
        }
    }

    if (property_exists($this, $key)) {
        if (isset($this->' . $this->mappingClassesPropertyName . '[$key])) {
            $this->{$key} = new $this->' . $this->mappingClassesPropertyName . '[$key]($val);
            if (method_exists($this->{$key}, "getAll")) {
                $this->{$key} = $this->{$key}->getAll();
            }
        } else {
            $this->{$key} = $val;
        }
    }
}
return $this;
                        ',
                        'docblock' => DocBlockGenerator::fromArray(
                            array(
                                'shortDescription' => 'Set from array',
                                'longDescription' => null,
                                'tags' => array(
                                    new Tag\ParamTag('data', 'array'),
                                    new Tag\ReturnTag(
                                        array(
                                            'datatype' => '$this',
                                        )
                                    ),
                                )
                            )
                        ),
                    )
                ),
                MethodGenerator::fromArray(
                    array(
                        'name' => 'fromJson',
                        'parameters' => array('json'),
                        'body' => '
$this->fromArray(json_decode($json, true));
return $this;
                        ',
                        'docblock' => DocBlockGenerator::fromArray(
                            array(
                                'shortDescription' => 'Set from json',
                                'longDescription' => null,
                                'tags' => array(
                                    new Tag\ParamTag('json', 'string'),
                                    new Tag\ReturnTag(
                                        array(
                                            'datatype' => '$this',
                                        )
                                    ),
                                )
                            )
                        ),
                    )
                ),
                MethodGenerator::fromArray(
                    array(
                        'name' => 'toArray',
                        'body' => 'return $this->toArrayRecursive($this);',
                        'docblock' => DocBlockGenerator::fromArray(
                            array(
                                'shortDescription' => 'Get array from object',
                                'longDescription' => null,
                                'tags' => array(
                                    new Tag\ReturnTag(
                                        array(
                                            'datatype' => 'array',
                                        )
                                    ),
                                )
                            )
                        ),
                    )
                ),
                MethodGenerator::fromArray(
                    array(
                        'name' => 'toJson',
                        'body' => 'return json_encode($this->toArrayRecursive($this));',
                        'docblock' => DocBlockGenerator::fromArray(
                            array(
                                'shortDescription' => 'Get array from object',
                                'longDescription' => null,
                                'tags' => array(
                                    new Tag\ReturnTag(
                                        array(
                                            'datatype' => 'string',
                                        )
                                    ),
                                )
                            )
                        ),
                    )
                ),
                MethodGenerator::fromArray(
                    array(
                        'name' => 'toArrayRecursive',
                        'parameters' => array('data'),
                        'visibility' => MethodGenerator::VISIBILITY_PROTECTED,
                        'body' => '
if (is_array($data) || is_object($data)) {
    $result = array();
    foreach ($data as $key => $value) {
        if ($key === "mappingClasses") {
            continue;
        }
        if (is_object($value) && method_exists($value, "getAll")) {
            $result[$key] = $this->toArrayRecursive($value->getAll());
        } else {
            if ($value !== null) {
                $result[$key] = $this->toArrayRecursive($value);
            }
        }
    }
    return $result;
}
return $data;',
                        'docblock' => DocBlockGenerator::fromArray(
                            array(
                                'shortDescription' => 'Get array from object',
                                'longDescription' => null,
                                'tags' => array(
                                    new Tag\ParamTag('data', 'array|object'),
                                    new Tag\ReturnTag(
                                        array(
                                            'datatype' => 'array',
                                        )
                                    ),
                                )
                            )
                        ),
                    )
                ),
            )
        );

        $class->addProperty($this->mappingClassesPropertyName, array(), PropertyGenerator::FLAG_PROTECTED);

        $file = new FileGenerator(
            array(
                'classes' => array($class),
            )
        );

        $code = $file->generate();

        $path = realpath($this->destinationDir) . '/' . $this->rootClassName . '.php';
        $code = str_replace("\n\n}\n", '}', $code); //PSR-2 ending of class
        file_put_contents($path, $code);
        return $path;
    }


    /**
     * @param string $property
     * @param string $type
     * @return array
     */
    protected function generateGetMethod($property, $type)
    {
        return array(
            'name' => 'get' . ucfirst($property),
            'body' => 'return $this->' . lcfirst($property) . ';',
            'docblock' => DocBlockGenerator::fromArray(
                array(
                    'shortDescription' => 'Retrieve the ' . $property . ' property',
                    'longDescription' => null,
                    'tags' => array(
                        new Tag\ReturnTag(
                            array(
                                'datatype' => $type . '|null',
                            )
                        ),
                    ),
                )
            ),
        );
    }

    /**
     * @param string $property
     * @param string $type
     * @return array
     */
    protected function generateSetMethod($property, $type)
    {
        return array(
            'name' => 'set' . ucfirst($property),
            'parameters' => array(lcfirst($property)),
            'body' => '$this->' . lcfirst($property) . ' = $' . lcfirst($property) . ';' . "\n"
                . 'return $this;',
            'docblock' => DocBlockGenerator::fromArray(
                array(
                    'shortDescription' => 'Set the ' . $property . ' property',
                    'longDescription' => null,
                    'tags' => array(
                        new Tag\ParamTag($property, $type),
                        new Tag\ReturnTag(
                            array(
                                'datatype' => '$this',
                            )
                        ),
                    )
                )
            ),
        );
    }


    /**
     * @param string $sourceFile
     * @return string
     */
    protected function generateClass($sourceFile)
    {
        $sourceContent = json_decode(file_get_contents($sourceFile));
        $class = new ClassGenerator();

        if ($this->rootClassName) {
            $class->setExtendedClass($this->rootClassName);
        }

        $className = null;
        $mappingClasses = array();
        foreach ($sourceContent as $property => $value) {

            if ($property === '@name') {
                //Class name
                $className = $value;
                if ($this->namespace) {
                    $class->setNamespaceName($this->namespace);
                }

                $class->setName($value);

            } elseif ($property === '@type') {
                continue;
            } elseif ($value === 'number' || $value === 'int' || $value === 'integer') {
                //Create property type number
                $class->addProperty($property);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, 'int')),
                        MethodGenerator::fromArray($this->generateSetMethod($property, 'int')),
                    )
                );
            } elseif ($value === 'float' || $value === 'double' || $value === 'real') {
                //Create property type number
                $class->addProperty($property);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, $value)),
                        MethodGenerator::fromArray($this->generateSetMethod($property, $value)),
                    )
                );
            } elseif ($value === 'string') {
                //Create property type string
                $class->addProperty($property);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, $value)),
                        MethodGenerator::fromArray($this->generateSetMethod($property, $value)),
                    )
                );
            } elseif ($value === 'date') {
                //Create property type date
                $class->addProperty($property);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, 'string')),
                        MethodGenerator::fromArray($this->generateSetMethod($property, 'string')),
                    )
                );
            } elseif ($value === 'array') {
                //Create property type date
                $class->addProperty($property);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, 'array')),
                        MethodGenerator::fromArray($this->generateSetMethod($property, 'array')),
                    )
                );
            } elseif ($value === 'boolean' || $value === 'bool') {
                //Create property type boolean
                $class->addProperty($property);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, $value)),
                        MethodGenerator::fromArray($this->generateSetMethod($property, $value)),
                    )
                );
            } elseif ($property === "@model") {

                if ($this->namespace) {
                    $class->addUse($this->namespace . '\\' . ucfirst($value));
                }

            } elseif ($property === "@collection") {
                $class->addProperty('collection');
                $class->addMethods($this->getMethodsForCollection($value->model));
            } elseif ($property === "@parent") {
                //"@parent": "\\Classes\\Items",
                $class->setExtendedClass($value);
            } elseif (strpos($value, '@') === 0) {

                if ($className !== ucfirst(substr($value, 1))) {
                    if ($this->namespace) {
                        $class->addUse($this->namespace . '\\' . ucfirst(substr($value, 1)));
                    }
                }
                if ($this->namespace) {
                    $mappingClasses[$property] = $this->namespace . '\\' . ucfirst(substr($value, 1));
                } else {
                    $mappingClasses[$property] = ucfirst(substr($value, 1));
                }

                //Create property type Class
                $class->addProperty($property);
                $class->addMethods(
                    array(
                        // Method passed as array
                        MethodGenerator::fromArray($this->generateGetMethod($property, ucfirst(substr($value, 1)))),
                        MethodGenerator::fromArray($this->generateSetMethod($property, ucfirst(substr($value, 1)))),
                    )
                );
            } else {
                var_dump($value, $property);
                exit;
            }
        }

        $class->addProperty($this->mappingClassesPropertyName, $mappingClasses, PropertyGenerator::FLAG_PROTECTED);

        $file = new FileGenerator(
            array(
                'classes' => array($class),
            )
        );

        $code = $file->generate();

        $path = realpath($this->destinationDir) . '/' . ucfirst($className) . '.php';
        $code = str_replace("\n\n}\n", '}', $code);
        file_put_contents($path, $code);
        return $path;
    }

    /**
     * @param string $modelName
     * @return array
     */
    protected function getMethodsForCollection($modelName)
    {
        return array(
            // Method passed as array
            MethodGenerator::fromArray(
                array(
                    'name' => 'add',
                    'parameters' => array(lcfirst($modelName)),
                    'body' => '
if (is_array($' . lcfirst($modelName) . ')) {
    $this->collection[] = new ' . ucfirst($modelName) . '($' . lcfirst($modelName) . ');
} elseif (is_object($' . lcfirst($modelName) . ') && $' . lcfirst($modelName) . ' instanceof ' . ucfirst($modelName)
        . ') {
    $this->collection[] = $' . lcfirst($modelName) . ';
}

return $this;
',
                    'docblock' => DocBlockGenerator::fromArray(
                        array(
                            'shortDescription' => 'Add item',
                            'longDescription' => null,
                            new Tag\ParamTag($modelName, ucfirst($modelName)),
                            new Tag\ReturnTag(
                                array(
                                    'datatype' => '$this',
                                )
                            ),
                        )
                    ),
                )
            ),
            MethodGenerator::fromArray(
                array(
                    'name' => 'getAll',
                    'body' => 'return $this->collection;',
                    'docblock' => DocBlockGenerator::fromArray(
                        array(
                            'shortDescription' => 'Get items',
                            'longDescription' => null,
                            new Tag\ParamTag($modelName, ucfirst($modelName)),
                            new Tag\ReturnTag(
                                array(
                                    'datatype' => '$this',
                                )
                            ),
                        )
                    ),
                )
            )
        );
    }
}
