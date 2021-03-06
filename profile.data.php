<?php
require_once 'functions.php';
require_once 'functions.profile.php';

$sub_page = 4;

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php echo $header ?>
    <title>Datasets | <?php echo SITENAME ?></title>
  </head>

  <body class="profile">

<?php require_once 'include.header.php'; ?>

<h1>Datasets</h1>

<p>
  At the Metabolism of Cities website we are working hard to make one central, publicly accessible database
  that provides visitors with all the data obtained by urban metabolism research. This dataset can easily 
  be searched, visualized on a map, and downloaded. Our purpose is to make research data more accessible and make
  it easier to see which numbers have been obtained by previous research. All datapoints are linked to the
  original study and full credit is given to the researchers involved.
</p>

<p>
  We would like to ask you to add any data points that you have obtained through urban metabolism research. 
  We are looking for any piece of data including per-capita indicators, total input, output, or data on 
  particular subflows.
</p>

<h2>How to add data?</h2>

<h3>Directly enter the data</h3>

<p>
  You can enter data directly into our system. Entering data is simple and fast, and practical if you have a relatively small 
  list of data points (less than 30). 
</p>

<p>
  <a href="profile/<?php echo $profile_id ?>/data-entry" class="btn btn-primary">Start now</a>
</p>

<h3>E-mail us your data</h3>

<p>
  If you have a large list of data points, then it is much easier to e-mail us your data. An spreadsheet will be most practical
  for this purpose. E-mail your data to <a href="mailto:<?php echo EMAIL ?>"><?php echo EMAIL ?></a> and we'll take it 
  from there.
</p>

<h2>FAQ</h2>

<h3>Who owns the data?</h3>

<p>
  You do. Or whoever owns the data at the moment. We only dissemminate data that has already been published. We link
  to the original source whenever we allow people to download the data. In no way is ownership transferred by adding 
  your data to our website.
</p>

<h3>What data can I enter?</h3>

<p>
  We are looking for urban or regional metabolism data. The data points range from indicators (e.g. DMI, DMC, NAS)
  to consumption or input/output data like CO2 emissions per capita or energy use per capita. 
</p>

<h3>Should data have been published before?</h3>

<p>
  Yes.<br />
  We are not looking to publish original research in this section. However, data can be published in many forms:
  as a thesis, peer-reviewed publication, or as a (research) report.
</p>



<?php require_once 'include.footer.php'; ?>

  </body>
</html>
