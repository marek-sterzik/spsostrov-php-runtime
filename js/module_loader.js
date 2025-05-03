import $ from "jquery"

$(function () {
    var modulesRaw = $('body').attr('data-modules')
    if (modulesRaw == undefined) {
        return
    }

    modulesRaw = modulesRaw.replaceAll(/( |\n)+/g, ' ')
    const modules = modulesRaw.split(' ')

    for (var moduleName of modules) {
        if (moduleName.trim().length === 0 || moduleName.includes(".")) {
            continue
        }

        import(/* webpackMode: "eager" */ `./modules/${moduleName}`)
            .then(module => {}, error => console.error(error))
    }
})
