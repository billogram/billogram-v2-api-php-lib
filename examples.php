<?php

/* Include the Billogram API library */
use Billogram\Api as BillogramAPI;
use Billogram\Api\Exceptions\ObjectNotFoundError;

/* Sample autoloader function for PHP. For most applications, you usually
   already have one of these registered.
*/
function autoload($className)
{
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) .
            DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    require $fileName;
}
spl_autoload_register('autoload');

/* Load an instance of the Billogram API library using your API ID and
   API password. You can also pass an app identifier for better debugging.
   For testing you will most likely also use another API base url. */
$apiId = '1234-sampleId';
$apiPassword = '0123456789abcdef0123456789abcdef';
$identifier = 'PHP API Example';
$apiBaseUrl = 'https://billogram.com/api/v2';
$api = new BillogramAPI($apiId, $apiPassword, $identifier, $apiBaseUrl);

/* Creates a customer */
echo '- Creating customer:' . "\n";
$customerObject = $api->customers->create(array(
    'name' => 'Company 1 AB',
    'address' => array(
        'street_address' => 'Street 22',
        'zipcode' => '12345',
        'city' => 'Stockholm',
        'country' => 'SE'
    ),
    'contact' => array(
        'email' => 'invoicing@example.org'
    )
));

echo 'Customer "' . $customerObject->name .
    '" created, with customer number: ' . $customerObject->customer_no . '.' .
    "\n";

echo "\n";

/* Create a billogram */
echo '- Creating billogram:' . "\n";
$signKey = uniqid(); // This could be whatever secret string you want.
echo 'The sign_key is set to "' . $signKey . '".' . "\n";
$billogramObject = $api->billogram->create(array(
    'invoice_date' => '2013-09-11',
    'due_date' => '2013-10-11',
    'currency' => 'SEK',
    'customer' => array(
        'customer_no' => $customerObject->customer_no,
    ),
    'invoice_fee' => 0,
    'items' => array(array(
        'count' => 1,
        'price' => 300,
        'vat' => 25,
        'title' => 'Test item',
    )),
    'callbacks' => array(
        'sign_key' => $signKey,
        'url' => 'http://example.org/billogram-callback'
    ),
));

$billogramId = $billogramObject->id;

echo 'Billogram "' . $billogramObject->id .
    '" created with a total sum: ' . $billogramObject->total_sum . ' ' .
    $billogramObject->currency . ', state "' . $billogramObject->state . '".' .
    "\n";

/* Send billogram with the delivery method "Letter", could also be "Email"
   or "Email+Letter". The $billogramObject will refresh it's data with
   up to date data. */
$billogramObject->send('Letter');

echo 'Billogram "' . $billogramObject->id .
    '" has been sent ' . $billogramObject->attested_at . ', state "' .
    $billogramObject->state . '".' . "\n";


/* Wait for the PDF file to be generated and then get the PDF content.
   Usually, we don't recommend waiting for the PDF content as the invoice will
   already be sent out to the customer (via Letter, or Email). */
echo 'Waiting for PDF to be generated, this may take a few seconds.' . "\n";
do {
    try {
        $pdfContent = $billogramObject->getInvoicePdf();
        break;
    } catch (ObjectNotFoundError $e) { // PDF has not been created yet.
        sleep(1);
    }
} while (true);
echo 'PDF content stored in $pdfContent (' . strlen($pdfContent) .
    ' bytes).' . "\n";

/* Credits the full amount of the billogram which will cause the billogram
   to generate a credit invoice and ultimately go to an ended state (in this
   case the state "Credited"). */
$billogramObject->creditFull();

echo 'Billogram "' . $billogramObject->id .
    '" has been credited, remaining sum: ' . $billogramObject->remaining_sum .
    ' ' . $billogramObject->currency . ', state "' . $billogramObject->state .
    '".' . "\n";

echo "\n";

/* Fetch an existing billogram by id. We stored the id in $billogramId earlier
   when we created the billogram in the first example. */
$billogramObject = $api->billogram->get($billogramId);

echo 'Billogram "' . $billogramObject->id . '" fetched. This is a ' .
    'billogram for "' . $billogramObject->customer->name . '".' . "\n";

echo "\n";

/* Fetch a set of a all customers, the default limit is to get 100 at a time
   but this limit could be increased to 500 at a time. */
echo '- Fetching customers' . "\n";
$customersQuery = $api->customers->query()->order('created_at', 'asc');
$totalPages = $customersQuery->totalPages();
for ($page = 1; $page <= $totalPages; $page++) {
    $customersArray = $customersQuery->getPage($page);
    /* Loop over the customersArray and do something with the customers
       here. */
}

echo $customersQuery->count() . ' customers returned.' . "\n";

echo "\n";

/* Fetch a set of a all unattested billogram. This time we filter on the
   'state' parameter and fetch 50 at a time. We'll also sort on due_date */
echo '- Fetching set of unattested billogram' . "\n";
$billogramQuery = $api->billogram->query()->
    pageSize(50)->
    filterField('state', 'Unattested')->
    order('due_date', 'asc');
$totalPages = $billogramQuery->totalPages();
for ($page = 1; $page <= $totalPages; $page++) {
    $billogramArray = $billogramQuery->getPage($page);
    /* Loop over the billogramArray and do something with the billogram
       here. */
    foreach ($billogramArray as $billogram) {
        /* For example we could send them by invoking the send() method.
           Note: However, if we do something with the first 50 here,
           page number 2 will not actually return the original 100-150
           (instead of 50-100 as we would want). */
        // $billogram->send('Email');
    }
}

echo $billogramQuery->count() . ' unattested billogram returned.' . "\n";

echo "\n";
