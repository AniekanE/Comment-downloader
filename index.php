<?php
// GET THE YOUTUBE VIDEO ID
//get submitted URL
$videoid ='';
if(isset( $_GET['url'])) {
    $youtube_url = $_GET['url'];

//Explode the url array for the video id
    $youtube_array = explode("=", $youtube_url);

    $videoid = $youtube_array[1];//Video id found in the youtube url exploded array
}

//Check if the download CSV button is pressed and call the createCsv function
if(isset($_POST['download'])){

    $videoid =	$_POST['videoid'];
    createCsv($videoid);
}


//Get response from youtube by calling the getcomment function
$response = getComments($videoid);

$comments = json_decode($response,true);
$commentsToDisplay = $comments;//varaible that holds the comments that is needed to be displayed

//Function to get Commments from google youtube api

function getComments($videoid,$nextpage = null){
    //PASS THE VIDEO ID AND GOOGLE API to the GOOGLE YOURTUBE API
    $url ='';
    if($nextpage==null){
        $url="https://www.googleapis.com/youtube/v3/commentThreads?key=AIzaSyA3tNT1UJRVvyQkUEl1-zr4oY3Eq3ZQAY0&textFormat=plainText&part=snippet,replies&videoId=" . $videoid."&maxResults=100";
    }else{

        $url="https://www.googleapis.com/youtube/v3/commentThreads?key=AIzaSyA3tNT1UJRVvyQkUEl1-zr4oY3Eq3ZQAY0&textFormat=plainText&part=snippet,replies&videoId=" . $videoid."&maxResults=100&pageToken=".$nextpage;
    }


//USE CURL to grab RESPONSE FROM YOUTUBE SERVER


    $ch = curl_init($url);
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
    );
    curl_setopt_array( $ch, $options );
    $response = curl_exec($ch);
    curl_close($ch);

//DECODE THE RESPONSE
    return $response;
}

//Function to create a CSV file
function createCsv($videoid)
{
    $response = getComments($videoid);
    $comments = json_decode($response,true);

    $commentsToDisplay = $comments;
    //SELECT AND ITERATE THE EXACT VALUE NEEDED FROM THE SERVER RESPONSE
    if(!empty($comments['items']))
    {
        // output headers so that the file is downloaded rather than displayed
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="comments.csv"');

        //Do not cache the file
        header('Pragma: no-cache');
        header('Expires: 0');

        $file = fopen('php://output','w');
        fputcsv($file,array('UserName','Date', 'Star rating','Comment'));

        foreach($comments['items'] as $item)
        {
            $authorName = $item['snippet']['topLevelComment']['snippet']['authorDisplayName'];
            $authorUrl = $item['snippet']['topLevelComment']['snippet']['authorChannelUrl'];
            $authorThumbNailUrl = $item['snippet']['topLevelComment']['snippet']['authorProfileImageUrl'];
            $comment = $item['snippet']['topLevelComment']['snippet']['textDisplay'];
            $date = $item['snippet']['topLevelComment']['snippet']['publishedAt'];
            $rating = $item['snippet']['topLevelComment']['snippet']['viewerRating'];
            fputcsv($file,array($authorName, date('M d,Y',strToTime($date)),$rating,$comment));

            if (!empty($item['replies']['comments'])) {

                foreach ($item['replies']['comments'] as $comment) {

                    $authorName = $comment['snippet']['authorDisplayName'];
                    $authorUrl = $comment['snippet']['authorChannelUrl'];
                    $authorThumbNailUrl = $comment['snippet']['authorProfileImageUrl'];
                    $comment1 = $comment['snippet']['textDisplay'];
                    $date = $comment['snippet']['publishedAt'];
                    $rating = $comment['snippet']['viewerRating'];

                    fputcsv($file,array($authorName, date('M d,Y',strToTime($date)),$rating,$comment1));
                }
            }
        }
    }
    //Continue to query the api for comments while the response still has the nextPageToken
    while( $comments['nextPageToken'] !=''){
        $response = getComments($videoid, $comments['nextPageToken']);
        $comments = json_decode($response,true);
        if(!empty($comments['items'])){
            foreach($comments['items'] as $item)
            {
                $authorName = $item['snippet']['topLevelComment']['snippet']['authorDisplayName'];
                $authorUrl = $item['snippet']['topLevelComment']['snippet']['authorChannelUrl'];
                $authorThumbNailUrl = $item['snippet']['topLevelComment']['snippet']['authorProfileImageUrl'];
                $comment = $item['snippet']['topLevelComment']['snippet']['textDisplay'];
                $date = $item['snippet']['topLevelComment']['snippet']['publishedAt'];
                $rating = $item['snippet']['topLevelComment']['snippet']['viewerRating'];

                fputcsv($file,array($authorName, date('M d,Y',strToTime($date)),$rating,$comment));

                if (!empty($item['replies']['comments'])) {

                    foreach ($item['replies']['comments'] as $comment) {

                        $authorName = $comment['snippet']['authorDisplayName'];
                        $authorUrl = $comment['snippet']['authorChannelUrl'];
                        $authorThumbNailUrl = $comment['snippet']['authorProfileImageUrl'];
                        $comment1 = $comment['snippet']['textDisplay'];
                        $date = $comment['snippet']['publishedAt'];
                        $rating = $comment['snippet']['viewerRating'];

                        fputcsv($file,array($authorName, date('M d,Y',strToTime($date)),$rating,$comment1));
                    }
                }
            }
        }
    }
    //terminate the csv generation to avoid html document download
    exit();
}

function prepareHtmlResponse($response) {

}

?>


<!DOCTYPE html>
<html>
<head>
    <title>Comment Downloader</title>

    <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="./style.css">
</head>
<header>
    Enter a <a href="www.youtube.com">YouTube</a> URL below to load the comments!
</header>
<body>

<div>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form1">
        <div class="row form1">
            <div class="form1">
                <label for="url">Enter YouTube URL</label>
            </div>
            <div class="">
                <input type="text" id="url" name="url" placeholder="Enter URL..."
                       value="<?php if(isset($youtube_url)){echo $youtube_url;} ?>"
                >
            </div>

            <button name="submit" class="button" type="submit">Submit</button>

        </div>
    </form>
</div>
<div class="comments">
    <?php
    if(!empty($commentsToDisplay['items'])){
        $i = 1;
        //echo a form with a download csv button and  hidden input with video id as value
        echo "
			<form method=\"post\" action=".$_SERVER['PHP_SELF'].">
			<input type=\"hidden\" name=\"videoid\" value=".$videoid.">
			<button type=\"submit\" name=\"download\">Download as Csv</button>
			</form>
			";
        echo "<table border=\"1\">
			<tr>
			<th>S/No</th>
			<th>UserName</th>
			<th>Date</th>
			<th>Star Rating</th>
			<th>Comment</th>
			</tr>";

        foreach($commentsToDisplay['items'] as $item) {


            $authorName = $item['snippet']['topLevelComment']['snippet']['authorDisplayName'];
            $authorUrl = $item['snippet']['topLevelComment']['snippet']['authorChannelUrl'];
            $authorThumbNailUrl = $item['snippet']['topLevelComment']['snippet']['authorProfileImageUrl'];
            $comment = $item['snippet']['topLevelComment']['snippet']['textDisplay'];
            $date = $item['snippet']['topLevelComment']['snippet']['publishedAt'];
            $rating = $item['snippet']['topLevelComment']['snippet']['viewerRating'];


            echo "<tr>";
            echo "<td>" . $i++ . "</td>";
            echo "<td>" . $authorName . "</td>";
            echo "<td>" . date('M d,Y',strToTime($date)) . "</td>";
            if($rating =='none'){
                echo "<td> </td>";
            }else{
                echo "<td>" . $rating . "</td>";
            }
            echo "<td>" . $comment . "</td>";
            echo "</tr>";

            if (!empty($item['replies']['comments'])) {

                foreach ($item['replies']['comments'] as $comment) {

                    $authorName = $comment['snippet']['authorDisplayName'];
                    $authorUrl = $comment['snippet']['authorChannelUrl'];
                    $authorThumbNailUrl = $comment['snippet']['authorProfileImageUrl'];
                    $comment1 = $comment['snippet']['textDisplay'];
                    $date = $comment['snippet']['publishedAt'];
                    $rating = $comment['snippet']['viewerRating'];

                    echo "<tr>";
                    echo "<td>" . $i++ . "</td>";
                    echo "<td>" . $authorName . "</td>";
                    echo "<td>" . date('M d,Y',strToTime($date)) . "</td>";
                    if($rating =='none'){
                        echo "<td> </td>";
                    }else{
                        echo "<td>" . $rating . "</td>";
                    }
                    echo "<td>" . $comment1 . "</td>";
                    echo "</tr>";
                }
            }
        }
        echo "</table>";
    }
    ?>


</div>

</body>

</html>
