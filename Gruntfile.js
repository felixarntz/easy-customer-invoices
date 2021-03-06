'use strict';
module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		pluginheader:	'/*\n' +
						'Plugin Name: Easy Customer Invoices\n' +
						'Plugin URI: <%= pkg.homepage %>\n' +
						'Description: <%= pkg.description %>\n' +
						'Version: <%= pkg.version %>\n' +
						'Author: <%= pkg.author.name %>\n' +
						'Author URI: <%= pkg.author.url %>\n' +
						'License: <%= pkg.license.name %>\n' +
						'License URI: <%= pkg.license.url %>\n' +
						'Text Domain: easy-customer-invoices\n' +
						'Domain Path: /languages/\n' +
						'Tags: <%= pkg.keywords.join(", ") %>\n' +
						'*/',
		fileheader:		'/**\n' +
						' * @package WPECI\n' +
						' * @version <%= pkg.version %>\n' +
						' * @author <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
						' */',

		clean: {
			admin: [
				'assets/admin.min.js'
			],
			stats: [
				'assets/stats.min.js'
			],
			translation: [
				'languages/easy-customer-invoices.pot'
			]
		},

		jshint: {
			options: {
				jshintrc: 'assets/.jshintrc'
			},
			admin: {
				src: [
					'assets/admin.js'
				]
			},
			stats: {
				src: [
					'assets/stats.js'
				]
			}
		},

		uglify: {
			options: {
				preserveComments: 'some',
				report: 'min'
			},
			admin: {
				src: 'assets/admin.js',
				dest: 'assets/admin.min.js'
			},
			stats: {
				src: 'assets/stats.js',
				dest: 'assets/stats.min.js'
			}
		},

		replace: {
			header: {
				src: [
					'easy-customer-invoices.php'
				],
				overwrite: true,
				replacements: [{
					from: /((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/,
					to: '<%= pluginheader %>'
				}]
			},
			version: {
				src: [
					'easy-customer-invoices.php',
					'inc/**/*.php'
				],
				overwrite: true,
				replacements: [{
					from: /\/\*\*\s+\*\s@package\s[^*]+\s+\*\s@version\s[^*]+\s+\*\s@author\s[^*]+\s\*\//,
					to: '<%= fileheader %>'
				}]
			}
		},

		makepot: {
			translation: {
				options: {
					mainFile: 'easy-customer-invoices.php',
					domainPath: '/languages',
					exclude: [ 'vendor/.*' ],
					potComments: 'Copyright (c) 2016-<%= grunt.template.today("yyyy") %> <%= pkg.author.name %>',
					potFilename: 'easy-customer-invoices.pot',
					potHeaders: {
						'language-team': '<%= pkg.author.name %> <<%= pkg.author.email %>>',
						'last-translator': '<%= pkg.author.name %> <<%= pkg.author.email %>>',
						'project-id-version': '<%= pkg.name %> <%= pkg.version %>',
						'report-msgid-bugs-to': '<%= pkg.homepage %>',
						'x-generator': 'grunt-wp-i18n 0.5.3',
						'x-poedit-basepath': '.',
						'x-poedit-language': 'English',
						'x-poedit-country': 'UNITED STATES',
						'x-poedit-sourcecharset': 'uft-8',
						'x-poedit-keywordslist': '__;_e;_x:1,2c;_ex:1,2c;_n:1,2; _nx:1,2,4c;_n_noop:1,2;_nx_noop:1,2,3c;esc_attr__; esc_html__;esc_attr_e; esc_html_e;esc_attr_x:1,2c; esc_html_x:1,2c;',
						'x-poedit-bookmars': '',
						'x-poedit-searchpath-0': '.',
						'x-textdomain-support': 'yes'
					},
					type: 'wp-plugin'
				}
			}
		}

 	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-text-replace');
	grunt.loadNpmTasks('grunt-wp-i18n');

	grunt.registerTask( 'admin', [
		'clean:admin',
		'jshint:admin',
		'uglify:admin'
	]);

	grunt.registerTask( 'stats', [
		'clean:stats',
		'jshint:stats',
		'uglify:stats'
	]);

	grunt.registerTask('translation', [
		'clean:translation',
		'makepot:translation'
	]);

	grunt.registerTask('plugin', [
		'replace:version',
		'replace:header'
	]);

	grunt.registerTask('default', [
		'admin',
		'stats',
		'translation'
	]);

	grunt.registerTask('build', [
		'admin',
		'stats',
		'translation',
		'plugin'
	]);
};
