<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Validator;

use Arikaim\Core\Collection\Collection;
use Arikaim\Core\Validator\Rule;
use Arikaim\Core\Validator\FilterBuilder;
use Arikaim\Core\Validator\RuleBuilder;
use Closure;

/**
 * Data validation
 */
class Validator extends Collection 
{
    /**
     * validation rules
     *
     * @var array
     */
    private $rules;
    
    /**
     * Filters
     *
     * @var array
     */
    private $filters;

    /**
     * Validation errors
     *
     * @var array
     */
    private $errors;

    /**
     * On valid callback
     *
     * @var Closure|null
     */
    private $onValidCallback = null;

    /**
     * On error callback
     *
     * @var Closure|null
     */
    private $onErrorCallback = null;

    /**
     * Get valida callback
     *
     * @var Closure|null
    */
    private $getValidCallback;
    
    /**
     * Get error callback
     *
     * @var Closure|null
     */
    private $getErrorCallback;

    /**
     * Constructor
     * 
     * @param array $data
     */
    public function __construct($data = [], Closure $getValidCallback = null, Closure $getErrorCallback = null) 
    {
        parent::__construct($data);
        
        $this->rules = [];
        $this->errors = [];
        $this->filters = [];
        $this->getValidCallback = $getValidCallback;
        $this->getErrorCallback = $getErrorCallback;
    }

    /**
     * Init callback
     *
     * @return void
     */
    protected function initCallback()
    {
        if (empty($this->onValidCallback) == true) {
            $this->onValidCallback = ($this->getValidCallback instanceof Closure) ? ($this->getValidCallback)() : null;
        }

        if (empty($this->onErrorCallback) == true) {
            $this->onErrorCallback = ($this->getErrorCallback instanceof Closure) ? ($this->getErrorCallback)() : null;
        }
    }

    /**
     * Set callback for validation done
     *
     * @param \Closure $callback
     * @return void
    */
    public function onValid(Closure $callback)
    {
        $this->onValidCallback = $callback; 
    }

    /**
     * Set callback for error valdation
     *
     * @param \Closure $callback
     * @return void
    */
    public function onError(Closure $callback)
    {
        $this->onErrorCallback = $callback; 
    }

    /**
     * Add validation rule
     *
     * @param string $fieldName
     * @param Rule|string $rule
     * @param string|null $error
     * 
     * @return Validator
     */
    public function addRule($rule, $fieldName = null, $error = null) 
    {                
        if (\is_string($rule) == true) {
            $rule = $this->rule()->createRule($rule,$error);
        }
        if (\is_object($rule) == true) {      
            $fieldName = (empty($fieldName) == true) ? $rule->getFieldName() : $fieldName;
            if (\array_key_exists($fieldName,$this->rules) == false) {
                $this->rules[$fieldName] = [];
            }
            \array_push($this->rules[$fieldName],$rule);                   
        } 

        return $this;
    }

    /**
     * Return rule builder
     *
     * @return RuleBuilder
     */
    public function rule()
    {  
        return new RuleBuilder();
    }

    /**
     * Return filter builder
     *
     * @return FilterBuilder
     */
    public function filter()
    {
        return new FilterBuilder();
    }    

    /**
     * Add filter
     *
     * @param string $fieldName
     * @param Filter $filter
     * @return Validator
     */
    public function addFilter($fieldName, Filter $filter) 
    {                   
        if (\is_string($filter) == true) {
            $filter = FilterBuilder::createFilter($fieldName,$filter);
        }
       
        if (\array_key_exists($fieldName,$this->filters) == false) {
            $this->filters[$fieldName] = [];
        }    
        \array_push($this->filters[$fieldName],$filter);                          
                    
        return $this;
    }
    
    /**
     * Sanitize form fields values
     *
     * @param array $data
     * @return Validator
     */
    public function doFilter($data = null) 
    {          
        if ($data != null) {
            $this->data = $data;
        }
      
        foreach ($this->data as $fieldName => $value) {     
            $filters = $this->getFilters($fieldName);            
            foreach ($filters as $filter) {
                if (\is_object($filter) == true) {
                    $this->data[$fieldName] = $filter->processFilter($this->data[$fieldName]);
                }
            }                 
        }      
      
        return $this;
    }

    /**
     * Sanitize and validate form
     *
     * @param array $data
     * @return void
     */
    public function filterAndValidate($data = null)
    {
        return $this->doFilter($data)->validate($data);
    }

    /**
     * Validate rules
     *
     * @param string $fieldName
     * @param array $rules
     * @return bool
     */
    public function validateRules($fieldName, $rules)
    {
        $value = $this->get($fieldName,null);
        $errors = 0;
        foreach ($rules as $rule) {    
            $valid = $this->validateRule($rule,$value);
            if ($valid == false) { 
                $errorCode = $rule->getError();
                $params = $rule->getErrorParams();               
                $this->addError($fieldName,$errorCode,$params); 
                $errors++;              
            }
        }

        return ($errors == 0);
    }

    /**
     * Validate rule
     *
     * @param Rule $rule
     * @param mxied $value
     * @return bool
     */
    public function validateRule($rule, $value)
    {
        if (empty($value) == true && $rule->isRequired() == false) {
            return true;
        }

        $type = $rule->getType();
        $ruleOptions = ($type == FILTER_CALLBACK) ? ['options' => [$rule, 'validate']] : [];          
        $result = \filter_var($value,$type,$ruleOptions); 

        return $result;
    }

    /**
     * Validate 
     *
     * @param array $data
     * @param array $rules
     * @return boolean
     */
    public function validate($data = null, $rules = null)
    {
        $this->errors = [];
        if (\is_array($data) == true) {
            $this->data = $data;
        }
           
        $this->initCallback();

        foreach ($this->rules as $fieldName => $rules) {  
            $this->validateRules($fieldName,$rules);
        }
       
        if ($this->isValid() == true) {
            // run data valid callback
            if ($this->onErrorCallback instanceof Closure) {
                ($this->onValidCallback)($this);  
            }  
            return true;
        }

        // run error callback       
        if ($this->onErrorCallback instanceof Closure) {
            ($this->onErrorCallback)($this->getErrors()); 
        }                      
        
        return false;   
    }

    /**
     * Set validation error
     *
     * @param string $fieldName
     * @param string $errorCode
     * @param array $params
     * @return void
     */
    public function addError($fieldName, $errorCode, array $params = [])
    {
        $error = [
            'field_name' => $fieldName,
            'error_code' => $errorCode,
            'params'     => $params
        ];
        \array_push($this->errors,$error);
    }

    /**
     * Sanitize form value
     *
     * @param mixed $value
     * @param int $type
     * @return void
     */
    public static function sanitizeVariable($value, $type = FILTER_SANITIZE_STRING) 
    {     
        return \filter_var(\trim($value),$type);       
    }

    /**
     * Return true if form is valid
     *
     * @return boolean
     */
    public function isValid()
    {
        return ($this->getErrorsCount() == 0);     
    }

    /**
     * Return validation errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Return number of errors
     *
     * @return int
     */
    public function getErrorsCount()
    {
        return \count($this->errors);
    }

    /**
     * Return validation rules
     *
     * @param string $fieldName
     * @return array
     */
    public function getRules($fieldName)
    {
        return (isset($this->rules[$fieldName]) == true) ? $this->rules[$fieldName] : [];          
    }

    /**
     * Return form filters
     *
     * @param string $fieldName
     * @return array
     */
    public function getFilters($fieldName)
    {   
        $all = (isset($this->filters['*']) == true) ? $this->filters['*'] : [];

        return (isset($this->filters[$fieldName]) == true) ? \array_merge($all,$this->filters[$fieldName]) : $all;          
    }
}
