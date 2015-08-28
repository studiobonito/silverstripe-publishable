# WARNING: Module depreciated

This SilverStripe module is depreciated please consider using the
[`silverstripe-versioneddataobjects`](https://github.com/heyday/silverstripe-versioneddataobjects) module instead.

# Publishable Module

## Overview

Publishable is a module for Silverstripe that provides a number of extensions that make enabling and managing versioning for DataObjects much simpler.

## Requirements

SilverStripe 3.1 or newer.

## Installation Instructions

Copy the 'publishable' folder to the root of your SilverStripe installation.

## Usage Overview

Add the publishing actions to ExampleObject and make it versionable

	ExampleObject::add_extension('Versioned("Stage", "Live")');
	ExampleObject::add_extension('Publishable');

Replace GridField with PublishableGridField on ExampleModelAdmin

	ExampleModelAdmin::add_extension('PublishableModelAdmin');

Create a new PublishableGridField to display the Example components

	$publishableGridField = new PublishableGridField('Example', 'Example', $this->Example());