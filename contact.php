<html>
  <head>
    <title>Contact</title>
    <script charset="utf-8" src="js/jquery-2.1.3.min.js"></script>
    <script charset="utf-8" src="js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="/css/bootstrap.min.css" media="screen" title="no title" charset="utf-8">
    <link rel="stylesheet" href="/css/bootstrap-theme.min.css" media="screen" title="no title" charset="utf-8">
  </head>
  <body>

    <?php require 'templates/navbar.php'; ?>

    <div class="container">
    	<div class="row">
          <div class="col-md-6 col-md-offset-3">
            <div class="well well-sm">
              <form class="form-horizontal" action="" method="post">
              <fieldset>
                <legend class="text-center">Contact us</legend>

                <!-- Name input-->
                <div class="form-group">
                  <label class="col-md-3 control-label" for="name">Name</label>
                  <div class="col-md-9">
                    <input id="name" name="name" type="text" placeholder="Your name" class="form-control">
                  </div>
                </div>

                <!-- Email input-->
                <div class="form-group">
                  <label class="col-md-3 control-label" for="email">Your E-mail</label>
                  <div class="col-md-9">
                    <input id="email" name="email" type="text" placeholder="Your email" class="form-control">
                  </div>
                </div>

                <!-- Message body -->
                <div class="form-group">
                  <label class="col-md-3 control-label" for="message">Your message</label>
                  <div class="col-md-9">
                    <textarea class="form-control" id="message" name="message" placeholder="Please enter your message here..." rows="5"></textarea>
                  </div>
                </div>

                <!-- Form actions -->
                <div class="form-group">
                  <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary btn-lg">Submit</button>
                  </div>
                </div>
              </fieldset>
              </form>
            </div>
          </div>
    	</div>
    </div>
  </body>
</html>
