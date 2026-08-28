/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const path = require('path')

module.exports = {
    entry: {
        'calendar-visio-plugin': path.join(__dirname, 'js', 'calendar-visio-plugin.js'),
    },
    output: {
        path: path.resolve(__dirname, 'js', 'dist'),
        publicPath: '/apps/calendar_visio_addon/js/dist/',
        filename: '[name].js',
        chunkFilename: '[name].js',
        clean: false,
    },
    resolve: {
        extensions: ['.js'],
    },
}
