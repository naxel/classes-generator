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
        foreach ($files as $file) {
            $path = $this->generateClass($file);
            $classes[$path] = $path;
        }

        if ($this->rootClassName) {
            $path = $this->generateRootClass();
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
        $class->setName($this->rootClassName);
        $class->setNamespaceName($this->namespace);
        $class->addMethods(
            array(
                MethodGenerator::fromArray(
                    array(
                        'name' => '__construct',
                        'parameters' => array('data = array()'),
                        'body' => '$this->setFromArray($data);',
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
                        'name' => 'setFromArray',
                        'parameters' => array('data'),
                        'body' => '
        foreach ($data as $key => $val) {

            if (is_int($key)) {
                if (method_exists($this, "addItem")) {
                    $this->addItem($val);
                }
            }

            if (property_exists($this, $key)) {
                if (isset($this->' . $this->mappingClassesPropertyName . '[$key])) {
                    $this->{$key} = new $this->' . $this->mappingClassesPropertyName . '[$key]($val);
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


    protected function generateGetMethod($property, $type)
    {
        return array(
            'name' => 'get' . ucfirst($property),
            'body' => 'return $this->' . $property . ';',
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

    protected function generateSetMethod($property, $type)
    {
        return array(
            'name' => 'set' . ucfirst($property),
            'parameters' => array($property),
            'body' => '$this->' . $property . ' = $' . $property . ';' . "\n"
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

        $path = realpath($this->destinationDir) . '/' . $className . '.php';
        $code = str_replace("\n\n}\n", '}', $code);
        file_put_contents($path, $code);
        return $path;
    }

    protected function getMethodsForCollection($modelName)
    {
        return array(
            // Method passed as array
            MethodGenerator::fromArray(
                array(
                    'name' => 'addItem',
                    'parameters' => array($modelName),
                    'body' => 'return $this->collection[] = new ' . ucfirst($modelName) . '($' . $modelName . ');',
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
            )
        );
    }
}
