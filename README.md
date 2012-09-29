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
	
Retrieve your database URL by issuing the following command (as per https://devcenter.heroku.com/articles/cleardb )

	$ heroku config | grep CLEARDB_DATABASE_URL
	
Ok, now we should add our config variables

	$ heroku config:add DATABASE_URL='mysql://adffdadf2341:adf4234@us-cdbr-east.cleardb.com/heroku_db?reconnect=true'

With this config set, now we can push to heroku
	$ git push heroku master
	
All DONE! Go to your heroku app in the admin, and all should work.. 

** ONE VERY IMPORTANT THING  **
While you can write to the heroku server, anything you upload to Heroku that isn't in Git will be wiped out the second that you deploy again. Most notably, if you upload Media to the wp-content/uploads/ directory, it will disappear after you deploy any changes. I guess this could be ok if ALL of your content is in the database (which isn't destroyed with a deploy), but it's definitely annoying. That said, I'm kind of new to Heroku, so there's gotta be a way to address this. 