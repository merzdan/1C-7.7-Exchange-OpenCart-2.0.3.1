<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-exchange" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-exchange" class="form-horizontal">
        <div class="form-group">
		    <label for="exchange_delete_check" class="col-sm-2  control-label"><?php echo $exchange_delete_check; ?> <input type="checkbox" value="1"  class="control-label" name="exchange_delete_check" id="exchange_delete_check"></label>
                               
		<div class="col-sm-4">
		 <select name="exchange_status" class="form-control">
               
                 <?php if ($exchange_status=='1') { ?>
                    <option value="1" selected="selected"><?php echo $text_delete; ?></option>
                    <option value="0"><?php echo $text_delete2; ?></option>
                    <option value="2"><?php echo $text_delete3; ?></option>
                    <option value="3"><?php echo $text_delete4; ?></option>
                  <?php } elseIf($exchange_status=='0') { ?>
                    <option value="1"><?php echo $text_delete; ?></option>
                    <option value="2"><?php echo $text_delete3; ?></option>
                    <option value="3"><?php echo $text_delete4; ?></option>
                    <option value="0" selected="selected"><?php echo $text_delete2; ?></option>
                    <?php } elseIf($exchange_status=='2') { ?>
                    <option value="1"><?php echo $text_delete; ?></option>
                    <option value="0"><?php echo $text_delete2; ?></option>
                    <option value="3"><?php echo $text_delete4; ?></option>
                    <option value="2" selected="selected"><?php echo $text_delete3; ?></option>
                    <?php } elseIf($exchange_status=='3') { ?>
                    <option value="1"><?php echo $text_delete; ?></option>
                    <option value="0"><?php echo $text_delete2; ?></option>
                    <option value="3" selected="selected" ><?php echo $text_delete4; ?></option>
                    <option value="2" ><?php echo $text_delete3; ?></option>
                  <?php }else{ ?>
                     <option value="1"><?php echo $text_delete; ?></option>
                     <option value="0"><?php echo $text_delete2; ?></option> 
                     <option value="3" selected="selected" ><?php echo $text_delete4; ?></option>
                     <option value="2" ><?php echo $text_delete3; ?></option>
                  <?php } ?>
                </select>
          </div>
		  
		 <div class="form-group">
                <div class="col-sm-4  control-label">
                            <label for="exchange_priority"><?php echo $exchange_priority_text; ?></label>
                                <input type="checkbox" value="1"  name="exchange_priority" id="exchange_priority" <?php echo ($exchange_priority == 1)? 'checked' : '';?>>
				</div>
		</div>
			
        <div class="form-group">
		   	<div class="col-sm-6 control-label">
                            <label for="exchange_auto"><?php echo $exchange_auto_text; ?></label>
                                <input type="checkbox" value="1"  name="exchange_auto" id="exchange_auto" <?php echo ($exchange_auto == 1)? 'checked' : '';?>>	
            </div>
			
			<div class="col-sm-6">
                            <label for="exchange_auto" class="control-label col-sm-4"><?php echo $exchange_auto_count_text; ?></label>
                             <div class="col-sm-4">
								<input type="text"  name="exchange_auto_count" id="exchange_auto_count" class="form-control" value="<?php echo $exchange_auto_count;?>"/>
							</div>
            </div>
	    </div>
		
        </div>
      </form>
    </div>
  </div>
</div>
<?php echo $footer; ?>