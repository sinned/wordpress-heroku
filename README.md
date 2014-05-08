# wordpress-heroku

Welcome. I wanted to deploy Wordpress on Heroku, and even though there were a few projects and tutorials out there, there was nothing out there that just had everything I needed to get up and running. Or perhaps I'm a lazy googler.

Installation
================

Clone the repository from Github

	$ git clone git://github.com/sinned/wordpress-heroku.git
	
Create a heroku app

	$ cd wordpress-heroku
	$ heroku create
	
Add a MySQL App to your app (in my case, I chose ClearDB)

	$ heroku addons:add cleardb:ignite
	
With this config set, now we can push to heroku
	$ git push heroku master
	
All DONE! Go to your heroku app in the admin, and all should work.. 

** ONE VERY IMPORTANT THING ABOUT UPLOADS **
Heroku's file system is "ephemeral". While you can write to the heroku server, anything you upload to Heroku that isn't in Git will be wiped out the second that you deploy again. Most notably, if you upload Media to the wp-content/uploads/ directory, it will disappear after you deploy any changes. I guess this could be ok if ALL of your content is in the database (which isn't destroyed with a deploy). But, for most people, wp-content/uploads/ is very useful, so now I'm looking at using S3 as the file system: https://devcenter.heroku.com/articles/s3 -- there are a few plugins that upload files to S3, so I'll be trying those out.

So, I've included the <a href="http://wordpress.org/plugins/amazon-s3-and-cloudfront/">Amazon S3 for Wordpress with Cloudfront</a> plugin to handle uploads -- to use, enable the Plugin and set it up with your AWS S3 credentials.

Also -- email from the server does not seem to work from Heroku, so I've included the <a href="http://wordpress.org/extend/plugins/wpmandrill/">wpmandrill</a> plugin that sends out all wordpress emails through the Mandrill Email API, which I love. I've also included the Mailgun plugin, which is just as good.