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

___

## Main Template:

Template Code        Purpose          


- ```HTML###|DESCRIPTION|###```   Displays episodes description
- ```HTML###|EMBEDVIDEO|###```   Displays embedded video of the episode
- ```HTML###|EXTERNALLINKS|###```  Displays external links to the video as per the external links template

___

## Main Post Template Example:
```HTML
<h3>Description</h3>
###|DESCRIPTION|###
<center>###|EMBEDVIDEO|###</center>
###|EXTERNALLINKS|###
```
![Image](https://i.imgur.com/uIN7ATO.png)
---

## External Links Template:
Template Code        Purpose 

- ```HTML###|EACHEXTERNALSTART|###``` Marks the beginning of the section that is used for each external link (refer to the examples)
- ```HTML###|THEEXTERNALLINK|###``` Use the url of the external link in the template HTML (in an anchor tag etc)
- ```HTML###|THEEXTERNALLINKDOMAIN|###``` Use the domain name of the external link in the template HTML (for the anchor text etc)
- ```HTML###|EACHEXTERNALEND|###``` Marks the end of the section that is used for each external link
---
## External Links Template Examples:

1. Show each external link as a paragraph using the links domain name as the anchor text for the link.
```HTML
<h3>External Links</h3>
###|EACHEXTERNALSTART|###
    <p><a href="###|THEEXTERNALLINK|###">###|THEEXTERNALLINKDOMAIN|###</a></p>
###|EACHEXTERNALEND|###
```
![Image](https://i.imgur.com/sDo2wff.png)

2. Show external links in a table with a watch and download link (download link leads to some CPI, CPA, affiliate offer or whatever)
```HTML
<h3>External Links</h3>
<table>
    ###|EACHEXTERNALSTART|###
        <tr><td><a href="###|THEEXTERNALLINK|###">###|THEEXTERNALLINKDOMAIN|###</a></td><td><a href="###|THEEXTERNALLINK|###">Watch Now</a></td><td><a href="http://TestLink.com">Download</a></td></tr>
    ###|EACHEXTERNALEND|###
</table>

```
![Image](https://i.imgur.com/4GUxQL0.png)


> Fair warning; sites like this are illegal to own and operate. If you are taking the risk then use offshore hosting and a domain that is registered somewhere that it cannot be seized from. I'm not responsible for your use of the plugin or any trouble you might have as a result of using it.
