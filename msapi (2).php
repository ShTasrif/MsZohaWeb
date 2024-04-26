<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');


$url = "http://www.mszohacollege.edu.bd/SMS/Student/GetStudentByStudentID";
$url2 = "http://www.mszohacollege.edu.bd/Accounts/MoneyReceiveDescriptionDetail/GetStudentPayableByYearAndStudentId";

$roll = isset($_GET['roll']) ? $_GET['roll'] : '';

if (empty($roll)) {
    echo json_encode(["error" => "Please provide an SSC Roll parameter."]);
    exit;
}

$data = array(
    "yearId" => "",
    "studentID" => $roll
);

// Initialize an array to store the results
$results = array();

function send_request($data, $year_id) {
    global $url, $url2, $results;

    // Update the yearId in the data
    $data["yearId"] = $year_id;
    
    // Convert the data to JSON format
    $data_json = json_encode($data);

    // Set headers for the request (if needed)
    $headers = array(
        "Content-Type: application/json"
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if ($response === false) {
        echo json_encode(["error" => "cURL error: " . curl_error($ch)]);
        return false;
    }

    // Decode the JSON response
    $response_sh = json_decode($response, true);

    // Check if "Name" is not null before adding to results
    if (!empty($response_sh["Name"])) {
        $formatted_response = format_response($response_sh, $year_id);
        // Store the result in the array
        $results[] = $formatted_response;
    } else {
        //echo json_encode(["error" => "Student data not found."]);
        return false;
    }

    // Close cURL session
    curl_close($ch);

    return true;
}

function format_response($response, $year_id) {
    global $url2;

    $yearly_payable = 0;
    $yearly_paid = 0;
    $yearly_discount = 0;
    $yearly_due = 0;
    $yearly_extra_fine = 0;

    $stdId = $response["StudentClass"]["SmsStudentId"];
    $payload = json_encode(array("yearId" => "1", "stdId" => $stdId));

    $headers2 = array(
        "Content-Type: application/json",
        "Referer: http://www.mszohacollege.edu.bd/SMS/StudentPayment/IndividualDue",
        "Cookie: ASP.NET_SessionId=ontkjwfs1metfqxzfvfwd5yb"
    );

    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $url2);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers2);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

    // Execute the cURL session
    $response2 = curl_exec($ch2);

    // Decode the JSON response
    $data2 = json_decode($response2, true);

    // Calculate yearly financial details
    foreach ($data2 as $entry) {
        $yearly_payable += $entry["Total"];
        $yearly_paid += $entry["MonthlyPaid"];
        $yearly_discount += $entry["Discount"];
        $yearly_due += $entry["MonthlyDue"];
        $yearly_extra_fine += $entry["ExtraFine"];
    }

    // Close cURL session
    curl_close($ch2);

    // Construct the formatted response
    $formatted_data = array(
        "Personal Information" => array(
            "Name" => $response["Name"],
            "Father Name" => $response["FatherName"],
            "Mother Name" => $response["MotherName"],
            "Father NID" => $response["FatherNID"],
            "Mother NID" => $response["MotherNID"],
            "Gender" => $response["Gender"],
            "Religion" => $response["ReligionName"],
            "Blood Group" => $response["BloodGroup"],
            "Date of Birth" => $response["DOBString"]
        ),
        "Educational Information" => array(
            "Roll No" => $response["RollNo"],
            "Student ID" => $response["StudentID"],
            "Student SMS ID" => $response["StudentClass"]["SmsStudentId"],
            "Section" => $response["SectionName"],
            "Session" => $response["StudentClass"]["Session"],
            "Group" => $response["GroupName"]
        ),
        "Contact Information" => array(
            "Email" => $response["Email"],
            "Guardian Email" => $response["GurdianEmail"],
            "Guardian Contact" => "0" . $response["GurdianContact"]
        ),
        "Personal Address" => array(
            "District" => $response["DistrictName"],
            "Upazila" => $response["UppuzillaName"],
            "Post Office" => $response["PostOffice"],
            "Village" => $response["Village"]
        ),
        "Yearly Bill" => array(
            "Yearly Payable" => $yearly_payable,
            "Yearly Paid" => $yearly_paid,
            "Yearly Discount" => $yearly_discount,
            "Yearly Due" => $yearly_due,
            "Yearly Extra Fine" => $yearly_extra_fine
        ),
        "PhotoUrl" => "mszohacollege.edu.bd/Content/Images/Student/" . $response["StudentID"] . ".JPG",
        "Password" => $response["Password"]
    );

    return $formatted_data;
}

// Send requests and store the results
send_request($data, "1");
send_request($data, "2");
send_request($data, "3");
send_request($data, "4");

// Output the final formatted JSON response
echo json_encode($results[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
?>
