<?php
namespace PaysonExpress{
     class Customer{
        /** @var string $city */
        private $city;
        /** @var string $country */
        private $country;
        /** @var int $dateOfBirth Date of birth YYMMDD (digits). */
        private $dateOfBirth;
        /** @var string $email */
        private $email;
        /** @var string $firstName */
        private $firstName;
        /** @var string $lastName */
        private $lastName;
        /** @var string $phone Phone number. */
        private $phone;
        /** @var string $postalCode Postal code. */
        private $postalCode;
        /** @var string $street Street address.*/
        private $street;
        /** @var string $type Type of customer ("business", "person" (default)).*/
        private $type;

        public function __construct($city = Null, $country = Null,$dateOfBirth = Null, $email = Null, $firstName = Null, $lastName = Null, $phone = Null, $postalCode = Null, $street = Null, $type = 'person'){
            $this->city = $city; 
            $this->country = $country;
            $this->dateOfBirth = $dateOfBirth;
            $this->email = $email;
            $this->firstName = $firstName;
            $this->lastName = $lastName;
            $this->phone = $phone;
            $this->postalCode = $postalCode;
            $this->street = $street;
            $this->type = $type;
        }
        
        public function setCity($city){
            $this->city = $city;     
        }
        
        public function getCity(){
            return $this->city;
        }
        
        public function setCountry($country){
            $this->country = $country;
        }
        
        public function getCountry(){
            return $this->country;
        }
        
        public function setDateOfBirth($dateOfBirth){
            $this->dateOfBirth = $dateOfBirth;
        }

        public function getDateOfBirth(){
            return $this->dateOfBirth;
        }
        
        public function setEmail($mail){
            $this->email = $email;
        }
        
        public function getEmail(){
            return $this->email;
        }
        
        public function setFirstName($firstName){
            $this->firstName = $firstName;
        }
        
        public function getFirstName(){
            return $this->firstName;
        }
        
        public function setLastName($lastName){
            $this->lastName = $lastName;
        }
        
        public function getLastName(){
            return $this->lastName;
        }

        public function setPhone($phone){
            $this->phone = $phone;
        }
        
        public function getPhone(){
            return $this->phone;
        }
        
        public function setPostalCode($postalCode){
            $this->postalCode = $postalCode;
        }
        
        public function getPostalCode(){
            return $this->postalCode;
        }
        
        public function setStreet($street){
            $this->street = $street;
        }
        
        public function getStreet(){
            return $this->street;
        }
        
        public function setType($type){
            $this->type = $type;
        }
        
        public function gettype(){
            return $this->type;
        } 
        
        /**
        * Returns the object of this class
        * 
        * @return string
        * @uses get_object_vars Description
        */
        public function getCustomerObject(){
            return get_object_vars($this);   
        }
    }
}
