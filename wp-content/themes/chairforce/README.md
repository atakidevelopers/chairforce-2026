# Chairforce

Chairforce Theme Boilerplate with Namespace and Gutenberg Block support

## Changes Required to start Development

1. Update Theme Comment Block in main file `/style.css`

2. After Adding more files as you go, use composer to update autoload if you need to. You shall need to have composer
   installed on your computer. In Terminal in the plugin directory, run following:
    * `composer update`

3. To install NPM dependencies, run the following command:
    * `npm install`

4. Create `.env` file for local server proxy
    * The file `.env` should be created under `\config-dev` directory
    * The local dev server proxy should be added like this: `PROXY=mylocalsite.test`

5. After doing all the magic of coding, run:
    * `npm run build`

6. While developing you may use the watcher by using the command:
    * `npm run start`

7. To Updates WordPress packages to the latest version:
    * `npm run packages-update`

8. Complete list of commands can be found
   here: [https://www.npmjs.com/package/@wordpress/create-block](https://www.npmjs.com/package/@wordpress/create-block)

## Dependencies

1. Node Version: `20.17.X`
2. PHP: `8.2`
