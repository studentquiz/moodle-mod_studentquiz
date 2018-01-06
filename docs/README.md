## Manuals

This directory contains the source to edit the manuals for StudentQuiz. 

The manuals are automatically built on 

[https://studentquiz.hsr.ch/docs/](https://studentquiz.hsr.ch/docs/) 

### Getting Started

These manuals are written in a simple, text-based markup language called reStructured text. 

We use sphinx to build a fully searchable HTML version. 


### Requirements

* Python: 
	[https://www.python.org/](https://www.python.org/)
	
	On Windows: Add Python to your PATH environment variable during the install process.
		
* Install Python Package manager: PIP:

        python get-pip.py
	

See: [https://pip.pypa.io/en/stable/installing/](https://pip.pypa.io/en/stable/installing/) on more information about trouble shooting the installation of PIP.

	
* Install sphinx and the sphinx autobuilder as well as the theme:
   
         pip install sphinx sphinx-autobuild sphinx_rtd_theme


See 
[http://www.sphinx-doc.org/en/stable/install.html](http://www.sphinx-doc.org/en/stable/install.html) for more information about installing sphinx.


### Work on the documentation using Sphinx Autobuild

If you are working on the documentation, navigate to the docs folder and use:

	cd <your-git-repo>/docs
	sphinx-autobuild . _build/html/ -B

to continously update the build in the browser (It will automatically open your browser to the correct local html site).

### Build the documentation locally

Open the command prompt in the studentquiz directory. 

(On Windows: Enter `cmd` into file explorer's address bar)

Enter the `docs` directory: 

	cd ./docs

You can build html pages locally using:

    make html 
	
The output is written to the folder `_build/html`. 

Open `_build/html/index.html` with your browser to see the result.
	

### Commit & Push
When ever you are happy with your local result, commit & push your changes.

	git add . && git commit -m "Upd docs" && git push

### Helpful Links 

To learn more about sphinx and see the full installation guide, visit:

[http://www.sphinx-doc.org/en/stable/tutorial.html](http://www.sphinx-doc.org/en/stable/tutorial.html)

