<?php
define('servername', 'localhost');
define('username', 'root');
define('password', '');
define('db', 'task');
global $conn;

$status=array
(
  "invalid_emails" => array(),
  "duplicate_records" => array(),
  "processed_records" => array()
);

if(!empty($_FILES["campaign-file"]["name"])){
    $conn = new PDO("mysql:host=".servername.";"."dbname=".db, username, password);

    function check_duplicate($email,$phone,$employee_id){
        global $conn;
        $query = $conn->prepare("Select employee_id from employees where(email=? or phone=? or employee_id=?)");
        $query->execute([$email,$phone,$employee_id]);
        if($query->rowCount()){
            return false;
        }else{
            return true;
        }
    }

    $csvFile = fopen($_FILES['campaign-file']['tmp_name'], 'r');
    fgetcsv($csvFile);
    $processed_records=0;
        while (($getData = fgetcsv($csvFile, 200, ";")) !== FALSE){
            if (filter_var($getData[2], FILTER_VALIDATE_EMAIL)) {
                    $phone=preg_replace('/^0/', '',
                    preg_replace('/[^0-9]/','', $getData[4]));
                    if(!check_duplicate($getData[2],$phone,$getData[3])){
                        array_push($status["duplicate_records"],$getData[3]);
                    }else{
                        $query = $conn->prepare("INSERT into employees (employee_id,name,surname,email,phone,point) 
                        values (?,?,?,?,?,?)");
                        $query->execute([$getData[3],$getData[0],$getData[1],$getData[2],$phone,$getData[5]]); 
                        $processed_records++;
                    }
                    
                }else{
                    array_push($status["invalid_emails"],$getData[2]);
                }
        }
        fclose($csvFile);
        $conn=null;
}
array_push($status["processed_records"],$processed_records);
echo json_encode($status);
?>