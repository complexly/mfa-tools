<?php
require_once 'functions.php';
$section = 3;
$page = 2;

$id = (int)$_GET['id'];
$hash = $_GET['hash'];

$encrypt = encrypt($id);
$encrypt = substr($encrypt, 0, 20);
if ($id && $encrypt != $hash) {
  die("Sorry, this link is invalid");
}

if ($id) {
  $info = $db->record("SELECT * FROM research WHERE id = $id");
}

if ($_GET['delete']) {
  $db->query("DELETE FROM research WHERE id = $id");
  $print = "Your project has been deleted";
}

if ($_POST) {
  $post = array(
    'researcher' => html($_POST['researcher']),
    'institution' => html($_POST['institution']),
    'supervisor' => html($_POST['supervisor']),
    'title' => html($_POST['title']),
    'status' => html($_POST['status']),
    'description' => html($_POST['description']),
    'target_finishing_date' => html($_POST['target_finishing_date']),
    'email' => html($_POST['email']),
  );
  if ($id) {
    $db->update("research",$post,"id = $id");
  } else {
    $db->insert("research",$post);
    $id = $db->lastInsertId();
    $added = true;
    $encrypt = encrypt($id);
    $encrypt = substr($encrypt, 0, 20);
    mail(EMAIL, "New research project added", "New project was added: " . URL . "research/$id", "From:automail@metabolismofcities.org");
  }
}

$status = array('ongoing', 'finished', 'paused', 'cancelled');

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php echo $header ?>
    <title>Add your research project | <?php echo SITENAME ?></title>
    <style type="text/css">
    textarea.form-control{height:170px}
    .right{float:right}
    </style>
  </head>

  <body>

<?php require_once 'include.header.php'; ?>

      <div class="jumbotron">
        <?php if ($print) { ?>
          <h2>Project deleted</h2>
          <div class="alert alert-success"><?php echo $print ?></div>
        <?php } elseif ($added) { ?>
        <h1>Your project was created</h1>
        <div class="alert alert-success">
            Thanks for adding your project information. <br />
            <br />
            <strong>Important</strong><br />
            The link to update your project information at any time is:<br />
            <a href="<?php echo URL ?>update/<?php echo $id ?>/<?php echo $encrypt ?>"><?php echo URL ?>update/<?php echo $id ?>/<?php echo $encrypt ?></a>
            <br />
            Save or bookmark this link for future reference.<br />
            <br />
            <a href="research/<?php echo $id ?>">View the research page here.</a>
        </div>

        <?php } else { ?>

        <?php if ($id) { ?>
          <a href="delete/<?php echo $id ?>/<?php echo $encrypt ?>" class="btn btn-danger right" onclick="javascript:return confirm('Are you sure?')">Permanently delete this project</a>
        <?php } ?>


        <h1><?php echo $id ? "Edit" : "Add" ?> your research project</h1>

        <?php if ($id) { ?>
          <?php if ($_POST) { ?>
            <div class="alert alert-success">
              Thanks for updating your project information. <br />
              <a href="research/<?php echo $id ?>">View the updated page here.</a>
            </div>
          <?php } ?>
        <?php } else { ?>
          <div class="alert alert-warning">
            Are you currently involved in an urban metabolism-related research project? Add it now! 
            After submitting this form, you will be provided a unique link. With this link, you can amend/remove
            the project details at any time.
          </div>
        <?php } ?>

        <form method="post" class="form form-horizontal">

          <div class="form-group">
            <label class="col-sm-2 control-label">Project title</label>
            <div class="col-sm-10">
              <input class="form-control" type="text" name="title" value="<?php echo $info->title ?>" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Researcher(s)</label>
            <div class="col-sm-10">
              <input class="form-control" type="text" name="researcher" value="<?php echo $info->researcher ?>" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Supervisor(s) / Coordinator(s)</label>
            <div class="col-sm-10">
              <input class="form-control" type="text" name="supervisor" value="<?php echo $info->supervisor ?>" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Institution</label>
            <div class="col-sm-10">
              <input class="form-control" type="text" name="institution" value="<?php echo $info->institution ?>" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Target completion date</label>
            <div class="col-sm-10">
              <input class="form-control" type="text" name="target_finishing_date" value="<?php echo $info->target_finishing_date ?>" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Status</label>
            <div class="col-sm-10">
              <select name="status" class="form-control">
              <?php foreach ($status as $key) { ?>
                <option value="<?php echo $key ?>"<?php if ($key == $info->status) { echo ' selected'; } ?>><?php echo $key ?></option>
              <?php } ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">Description</label>
            <div class="col-sm-10">
              <textarea class="form-control" name="description"><?php echo br2nl($info->description) ?></textarea>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label">E-mail</label>
            <div class="col-sm-10">
              <input class="form-control" type="email" name="email" value="<?php echo $info->email ?>" />
            </div>
          </div>

          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-primary"><?php echo _('Save'); ?></button>
            </div>
          </div>
        
        </form>
        <?php } ?>
      </div>

<?php require_once 'include.footer.php'; ?>

  </body>
</html>
