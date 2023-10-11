<?php

require dirname(__FILE__) . '/../../vendor/autoload.php';

define('OTTO_TOKEN', getenv('OTTO_TOKEN'));
define('ASANA_PAT', getenv('ASANA_PAT'));

// define the Asana custom field id and the value id for the released status
// the values are for the nsquared workspace, may need to be changed for other workspaces
define('ASANA_STATUS_CUSTOM_FIELD_ID', '1202523200665003');
define('ASANA_STATUS_RELEASED', '1202536918444602');


// Get variables passed from the command line.
$values = getopt(
	'',
	array('pr_numbers:')
);
// Validate required inputs.
if ( ! isset( $values['pr_numbers'] ) ) {
	echo "\nInput PR numbers like --pr_numbers=1,2,3,4\n";
	// do not fail the process when there are no merged PRs
	exit( 0 );
}

$pr_numbers = explode(',', $values['pr_numbers']);

if(empty($pr_numbers)){
	echo "\nReceived an empty string input with no PR numbers\n";
	exit( 0 );
}

$client = new \GuzzleHttp\Client();

// for each merged pr
foreach ($pr_numbers as $pr_number) {
	// get its details
	$pr_details = getPullRequestDetails(OTTO_TOKEN, 'nsquared-team', 'ssa-plugin', $pr_number);

	$string = $pr_details['body'];
	// get the associated asana tasks urls from PR description
	preg_match_all('#\bhttps?://app.asana[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $string, $match);

	$asana_urls = $match[0];
	// extract asana task ids 
	$asana_task_ids = extractAsanaTaskIdsFromUrls($asana_urls);

	// one PR may have multiple Asana tasks
	foreach ($asana_task_ids as $asana_task_id) {
		$authorization = "Bearer " . ASANA_PAT;
		markAsanaTaskReleased($client, $authorization,  $asana_task_id);
	}
}
// =====================
// function definitions
// =====================

function getPullRequestDetails($token, $user, $repo, $pr_number)
{
	try {
		$endpoint = "https://api.github.com/repos/$user/$repo/pulls/$pr_number";
		$auth         = 'Bearer ' . $token;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'user-agent: ci-server',
				'Accept: application/vnd.github+json',
				"Authorization: $auth",
			)
		);
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);
		$result = json_decode($result, true);
		return $result;
	} catch (\Throwable $th) {
		//throw $th;
		print_r($th);
		exit(1);
	}
}

function extractAsanaTaskIdsFromUrls($asana_urls)
{
	$asana_task_ids = array();
	foreach ($asana_urls as $url) {
		$asana_task_ids[] = explode('/', $url)[5];
	}
	return $asana_task_ids;
}

function updateCustomFieldOnAsanaTask($client, $authorization, $asana_task_id, $custom_field_id, $custom_field_new_value_id)
{
	$response = $client->request('PUT', "https://app.asana.com/api/1.0/tasks/$asana_task_id", [
		'headers' => [
			'Authorization' => $authorization,
			'Content-Type' => 'application/json'
		],
		'body' => "{
			\"data\": {
			  \"custom_fields\": {
				\"$custom_field_id\": \"$custom_field_new_value_id\"
			  }
			}
		  }"
	]);
}

function markAsanaTaskReleased($client, $authorization, $asana_task_id)
{
	updateCustomFieldOnAsanaTask($client, $authorization, $asana_task_id, ASANA_STATUS_CUSTOM_FIELD_ID, ASANA_STATUS_RELEASED);
}
