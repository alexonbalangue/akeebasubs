1. ABOUT THIS PACKAGE
******************************************

	This is a PHP sample application that allows you to:
		- add a new Contact (two type of forms: short form and complete form)
		- edit an existing Contact and update its profile
		- list all existing account Contacts
		- remove an existing Contact from all lists
		- unsubscribe an existing Contact


2. WHAT THIS PACKAGE CONTAINS
*******************************************

	Below is a list of files released as part of this build.
	[BaseFolder]\images - [DIR] - this directory contains all relevant images included in this sample
	[BaseFolder]\countries.txt - [FILE] - this text file contains the list with all countries available in ConstantContact. Each country is on a separate line, together with its country code.					
	[BaseFolder]\states.txt - [FILE] - this text file contains the list with all states available in ConstantContact. With this lines are populated comboboxes in our sample application.
	[BaseFolder]\style.css - [FILE] - StyleSheet used to beautify our Sample Application.  
	[BaseFolder]\cc_class.php - ConstantContact API class. This file contains the Class developed to communicate with ConstantContact API
	[BaseFolder]\header.php - File with a basic design for header. It contains the image and menu navigation which is included in all forms within the sample application.
	[BaseFolder]\index.php - default file for this sample application
	[BaseFolder]\list_contacts.php - php file which is displaying all the contacts for current account.
	[BaseFolder]\add_account.php - php file which is desinged to display the full `add contact` form. It contains all available fields in ConstantContact.
	[BaseFolder]\simple_form.php - same `add contact` form, just that this is the shorten form. It displays only few PERSONAL INFORMATION fields.
	[BaseFolder]\delete_contact.php - file that is executed when user click on REMOVE link from `LIST ALL CONTACTS` form. Normally by executing this script, it changes the STATE for selected contact to REMOVED.
	[BaseFolder]\edit_contact_step1.php - displays a simple search form for a contact. If specified contact exists, then user is redirected to Edit Contact form. Otherwise user is redirected to Add Contact form.
	[BaseFolder]\edit_contact.php - php file that is designed to edit a contact profile. Here User can modify ALL the fields available in ConstantContact.
	 

3. HOW TO GET STARTED
*******************************************



Please see Documentation attached to this sample application for advanced details.


4. VERSION AND CHANGES
*******************************************
Version number: 1.0.4
Release date: 11.16.2011 14:50:22

- Added isset conditional coding to make sure variables had something
	to output before attempting to output
- Added Try / Catch block to make sure credentials were updated
- Added Try / Catch block to make sure list was selected
	upon creation of a new contact or editing an existing
- Added logic to check to see if State name and State other
	were supplied during creation or modification and removed
	state Other to prevent errors.


Version number: 1.0.3
Release date: 02.11.2009 14:24:38

- Changed CURLOPT_GET to CURLOPT_HTTPGET to avoid warning on PHP5


Version number: 1.0.2
Release date: 28.10.2009 10:05:00

- Added ability to easily specify contact lists on forms
- Added toggle to specify if the lists are forced
- Added toggle to hide/show contact lists
- Removed old prefix based list specification

Version number: 1.0.1
Release date: 26.10.2009 13:55:36

- Fixed typo on getSubscribers function
- Added ability to specify 'forced' lists
- Added ability to hide or show 'forced' lists to users
- Fixed error messaging when adding an existing contact
- Compressed code by removing extra line breaks


Version number: 1.0
Release date: 22.07.2009 15:30:36