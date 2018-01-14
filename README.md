###### Fully automated TV streaming site WP plugin
100% Hands free fully automated TV streaming site plugin. I made this plugin to start a streaming site but abandoned the project as other priorities took over. Although it would have been automated in terms of updating the site it would still have needed traffic driven to it and I have several other projects to manage.

## Features:
- Auto posting of latest releases as they come out (using WP cron every 15 minutes).
- Post template for customisation of episode page.
- Each show gets its own category (auto created if doesn't exist yet).
- Video embed code for episode is auto generated.
- Tv show poster is scraped and used as the featured image for each post.

## Installation:
As usual for wordpress just upload the zip and activate the plugin. Then visit its setting page to adjust things to your liking. Posts will not be created automatically unless the "Enable auto posting of new episodes" is checked on the settings page.

## Templates:
The plugin uses templates for posts which you can see on the settings page. It is fairly straightforward, just use the default as is or add your own custom HTML. The external link template has a "each external link" template part that is easier explained in the examples than in words here.

## Main Template:

| Template Code       | Purpose          |
| ------------- |:-------------:|
| ### | DESCRIPTION | ###     | right-aligned |
| col 2 is      | centered      |
| zebra stripes | are neat      |
