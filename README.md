# Akeeba Subscriptions 5

**This version of Akeeba Subscriptions is written on FOF 3. It requires Joomla! 3.4 or later and PHP 5.4.0 or later.**

This branch contains the current, in-development version of Akeeba Subscriptions based on FOF 3. You can study this
code as a good example of a real world application utilising the full power of FOF 3. There are things in here you won't
find in the FOF 3 documentation.

If you are looking for earlier versions to build a component you can use on your Joomla! 2.5, 3.0, 3.1, 3.2 or 3.3 site
please check out either the 4.x branch (Akeeba Subscriptions 4.x, Joomla! 3.2 and later) or the 3.x branch
(Akeeba Subscriptions 3.x, Joomla! 2.5, 3.0, 3.1 and 3.2). Older versions can work on PHP 5.3.4 or later. 

## Build instructions

### Prerequisites

In order to build the installation packages of this component you will need to have the following tools:

* A command line environment. Using Bash under Linux / Mac OS X works best. On Windows you will need to run most tools through an elevated privileges (administrator) command prompt on an NTFS filesystem due to the use of symlinks.
* A PHP CLI binary in your path
* Command line Git executables
* PEAR and Phing installed, with the Net_FTP and VersionControl_SVN PEAR packages installed
* libxml and libsxlt command-line tools, only if you intend on building the documentation PDF files

You will also need the following path structure inside a folder on your system

* **akeebasubs** This repository. We will refer to this as the MAIN directory
* **buildfiles** [Akeeba Build Tools](https://github.com/akeeba/buildfiles)
* **fof3** [Framework on Framework](https://github.com/akeeba/fof) – WARNING! You need to check out the `fof3` branch.
* **strapper** [Akeeba Strapper](https://github.com/akeeba/strapper)

You must use the exact folder names specified here.

### Initialising the repository

All of the following commands are to be run from the MAIN directory.

1. You will first need to do the initial link with Akeeba Build Tools, running the following command

		php ../buildfiles/tools/link.php `pwd`
		
	or, on Windows:
	
		php ../buildfiles/tools/link.php %CD%
		
2. After the initial linking takes place, go inside the build directory:

		cd build
		
	and run the Phing task called link:
	
		phing link
		
	If you are on Windows make sure that you are running an elevated command prompt (run cmd.exe as Administrator)
	
### Useful Phing tasks

All of the following commands are to be run build directory inside the MAIN directory.

#### Symlinking to a Joomla! installation
This will create symlinks and hardlinks from your working directory to a locally installed Joomla! site. Any changes you perform to the repository files will be instantly reflected to the site, without the need to deploy your changes.

	phing relink -Dsite=/path/to/site/root
	
or, on Windows:

	phing relink -Dsite=c:\path\to\site\root
	
**Examples**

	phing relink -Dsite=/var/www/html/joomla
	
or, on Windows:
	
	phing relink -Dsite=c:\path\to\site\root\joomla

#### Relinking internal files

This is required after every major upgrade in the component and/or when new plugins and modules are installed. It will create symlinks from the various external repositories to the MAIN directory.

	phing link
	
#### Creating a dev release installation package

This creates the installable ZIP packages of the component inside the MAIN/release directory.

	phing git

Please note that it's necessary to do a package build for FOF with `git pull` and `phing git`commands in your copy of the FOF repository before building an Akeeba Subscriptions package. For more details see the package build instructions on the [FOF page](https://github.com/akeeba/fof). Failure to do so will either result in a failure to create a package, an uninstallable package or will end up overwriting the already installed FOF on your site with an older version, resulting in potentially severe issues in other FOF-based components.
	
#### Build the documentation in PDF format

This builds the documentation in PDF format using the DocBook XML sources found in the documentation directory.

	phing documentation
	
## Collaboration

If you have found a bug you can submit your patch by doing a Pull Request on GitHub.
