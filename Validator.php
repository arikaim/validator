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
use Arikaim\Core\Validator\Interfaces\FilterInterface;
use Arikaim\Core\Utils\Factory;
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
    private $rules = [];
    
    /**
     * Filters
     *
     * @var array
     */
    private $filters = [];

    /**
     * Validation errors
     *
     * @var array
     */
    private $errors = [];

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
    private $getValidCallback = null;
    
    /**
     * Get error callback
     *
     * @var Closure|null
     */
    private $getErrorCallback = null;

    /**
     *  Rule buidler
     *
     * @var object|null
     */
    private $builder = null;

    /**
     * Constructor
     * 
     * @param array $data
     * @param Closure|null $getValidCallback
     * @param Closure|null $getErrorCallback    
     */
    public function __construct(array $data = [], ?Closure $getValidCallback = null, ?Closure $getErrorCallback = null) 
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
    protected function initCallback(): void
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
     * @param Closure $callback
     * @return void
    */
    public function onValid(Closure $callback): void
    {
        $this->onValidCallback = $callback; 
    }

    /**
     * Set callback for error valdation
     *
     * @param Closure $callback
     * @return void
    */
    public function onError(Closure $callback): void
    {
        $this->onErrorCallback = $callback; 
    }

    /**
     * Add validation rule
     *
     * @param Rule|string $rule
     * @param string|null $fieldName    
     * @param string|null $fieldName
     * @param string|null $errorCode
     * @return Validator
     */
    public function addRule($rule, ?string $fieldName = null, ?string $errorCode = null) 
    {                
        if (\is_string($rule) == true) {
            $rule = RuleBuilder::createRule($rule,$errorCode);
        }
        if (\is_object($rule) == true) {      
            $fieldName = (empty($fieldName) == true) ? '*' : $fieldName;
            if (\array_key_exists($fieldName,$this->rules) == false) {
                $this->rules[$fieldName] = [];
            }
            \array_push($this->rules[$fieldName],$rule);                   
        } 

        return $this;
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
     * @param string|null $fieldName
     * @param Filter|string $filter
     * @param array $args
     * @return Validator
     */
    public function addFilter(?string $fieldName, $filter, array $args = []) 
    {                   
        $fieldName = (empty($fieldName) == true) ? '*' : $fieldName;
        if (\is_string($filter) == true) {
            $filter = Factory::createInstance(Factory::getValidatorFiltersClass($filter),$args);                   
        }
       
        if ($filter instanceof FilterInterface) {
            if (\array_key_exists($fieldName,$this->filters) == false) {
                $this->filters[$fieldName] = [];
            }    
            \array_push($this->filters[$fieldName],$filter);    
        }
                                                 
        return $this;
    }
    
    /**
     * Sanitize form fields values
     *
     * @param array|null $data
     * @return Validator
     */
    public function doFilter(?array $data = null) 
    {         
        if (empty($data) == false) {
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
     * @param array|null $data
     * @return bool
     */
    public function filterAndValidate(?array $data = null): bool
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
    public function validateRules(string $fieldName, array $rules): bool
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
     * @param mixed $value
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
     * @param array|null $data
     * @return boolean
     */
    public function validate(?array $data = null): bool
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
            if ($this->onValidCallback instanceof Closure) {
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
     * @param string|null $errorCode
     * @param array $params
     * @return void
     */
    public function addError(string $fieldName, ?string $errorCode, array $params = []): void
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
     * @return mixed
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
    public function isValid(): bool
    {
        return (\count($this->errors) == 0);     
    }

    /**
     * Return validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Return number of errors
     *
     * @return int
     */
    public function getErrorsCount(): int
    {
        return \count($this->errors);
    }

    /**
     * Return validation rules
     *
     * @param string $fieldName
     * @return array
     */
    public function getRules(string $fieldName): array
    {
        return $this->rules[$fieldName] ?? [];          
    }

    /**
     * Return form filters
     *
     * @param string $fieldName
     * @return array
     */
    public function getFilters($fieldName)
    {   
        $all = $this->filters['*'] ?? [];

        return (isset($this->filters[$fieldName]) == true) ? \array_merge($all,$this->filters[$fieldName]) : $all;          
    }

    /**
     * Get value from collection
     *
     * @param string $key Name
     * @param mixed $default If key not exists return default value
     * @return mixed
     */
    public function get(string $key, $default = null)
    {       
        $item = $this->data[$key] ?? $default;
        if (\is_array($item) == true) {
            return $item;
        }
        if (\is_string($item) == true) {
            return \trim($item);
        }

        return $item;        
    }

    /**
     * Get item 
     *
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key) 
    {
        return $this->get($key);
    }
}
