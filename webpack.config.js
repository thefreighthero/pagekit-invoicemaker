export default [

    {
        entry: {
            /*admin views*/
            'invoicemaker-invoice': './app/views/admin/invoice.js',
            'invoicemaker-invoices': './app/views/admin/invoices.js',
            'invoicemaker-settings': './app/views/admin/settings.js',
        },
        output: {
            filename: './app/bundle/[name].js',
        },
        externals: {
            'lodash': '_',
            'jquery': 'jQuery',
            'uikit': 'UIkit',
            'vue': 'Vue',
        },
        module: {
            loaders: [
                {test: /\.vue$/, loader: 'vue',},
                {test: /\.html$/, loader: 'vue-html',},
                {test: /\.js/, loader: 'babel', query: {presets: ['es2015',],},},
            ],
        },

    },

];
