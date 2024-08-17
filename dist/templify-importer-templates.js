jQuery(function ($) {
    // Initialize configData
    let configData = null;
    let progressInterval = null;

    // Perform AJAX request
    $.ajax({
        url: templify_importer_templates.ajax_url,
        type: 'GET',
        data: {
            action: 'get_custom_filter_data'
        },
        success: function(response) {
            Object.keys(response.data).forEach(function(key) {
                var obj = response.data[key];
                if (obj && obj.hasOwnProperty('slug')) {
                    configData = obj;
                    return false; // Exit forEach loop once found
                }
            });

            if (configData) {
                var image = configData.image;
                var name = configData.name;
                var plugins = configData.plugins;
                var slug = configData.slug;

                var html = '<div class="templify_theme_starter_dash_inner">';
                    html += '<div class="main-panel">';
                        html += '<div class="templify-site-grid-wrap">';
                            html += '<div class="templates-grid" id="ImageTitleDiv">';
                                html += '<div class="templify-importer-main-panel">';
                                    html += '<div class="kst-template-item" data-plugins="'+plugins+'">';
                                        html += '<button type="button" class="components-button kst-import-btn is-small">';
                                            html += '<div class="LazyLoad is-visible">';
                                                html += '<img src="' + image + '" alt="' + name + '">';
                                            html += '</div>';
                                            html += '<div class="demo-title"><h4>' + name + '</h4></div>';
                                        html += '</button>';
                                    html += '</div>';
                                html += '</div>';
                            html += '</div>';
                            html += '<div class="templates-grid" id="PluginDiv" style="display:none">';
                                html += '<div class="kst-template-item" style="width:32%;padding-bottom:20% !important;background:#fff">';
                                    html += '<button type="button" class="components-button kst-import-btn is-small">';
                                        html += '<div class="LazyLoad is-visible">';
                                            html += '<div class="plugin-container" style="height:auto;text-align:left;padding: 10px 20px">';
                                                html += '<ul id="plugins-list"></ul>';
                                            html += '</div>';
                                        html += '</div>';
                                        html += '<div class="demo-title" style="display:flex;justify-content:space-between"><h4>Required Plugin</h4><a id="import-btn" type="button" style="float: right;margin-right: 10px;background: #000;color: #fff;font-weight: bold;font-size: 11px;padding: 4px;"> Import</a></div>';
                                    html += '</button>';
                                html += '</div>';
                            html += '</div>';
                        html += '</div>';
                    html += '</div>';
                html += '</div>';

                $('.templify_importer_dashboard_main').after(html);
            } else {
                console.log('No object with required properties found in response.');
            }
        },
        error: function(error) {
            console.log('Error:', error);
        }
    });

    $(document).on('click', '#ImageTitleDiv .kst-template-item', function(e) {
        e.preventDefault();
    
        var pluginsDataString = $(this).data('plugins');
        var pluginsData = pluginsDataString.split(',');
    
        console.log('Plugins data array:', pluginsData);
        $.ajax({
            url: templify_importer_templates.ajax_url,
            type: 'POST',
            data: {
                action: 'display_selected_plugin',
                plugins: JSON.stringify(pluginsData)
            },
            success: function(response) {
                if (response.success) {
                    var pluginsList = response.data;
                    $('#PluginDiv').css('display', 'block');
                    $('#ImageTitleDiv').css('display', 'none');
                    $('#plugins-list').empty();
                    $.each(pluginsList, function(index, plugin) {
                        var pluginName = plugin.name.split('/')[0];
                        var checked = plugin.is_active ? 'checked' : '';
                        $('#plugins-list').append('<li><input type="checkbox" class="PluginCheckBox" value="' + plugin.name + '" ' + checked + '>' + pluginName + '</li>');
                    });
                } else {
                    var pluginName = response.data.split('/')[0];
                    $('#plugins-list').html('<li><input type="checkbox" class="PluginCheckBox" value="' + response.data + '">' + pluginName + '</li>');
                }
            },
            error: function(error) {
                console.log('Error:', error);
            }
        });
    });
    

    $(document).on('click', '#import-btn', function(e) {
        e.preventDefault();

        var selectedPlugins = [];
        $('.PluginCheckBox:checked').each(function() {
            selectedPlugins.push($(this).val());
        });

        if (selectedPlugins.length > 0) {
            showProgressModal();

            console.log('Selected Plugins: ' + selectedPlugins.join(', '));
            $.ajax({
                url: templify_importer_templates.ajax_url,
                type: 'POST',
                data: {
                    action: 'templify_import_install_plugins',
                    plugins: JSON.stringify(selectedPlugins),
                    selected: configData.slug,
                    builder: "custom"
                },
                success: function(response) {
                    console.log('Full response:', response);
                    
                    // Stop the progress simulation once response is received
                    clearInterval(progressInterval);

                    if (response && response.status) {
                        if (response.status === 'pluginSuccess') {
                            console.log('Plugin installation was successful.');
                            initialAjaxCall(configData, configData.slug);
                        } else {
                            console.log('Unexpected response status:', response.status);
                        }
                    } else {
                        console.log('Response is missing or does not have a status property.');
                    }
                },
                error: function(error) {
                    console.log('Error:', error);
                    clearInterval(progressInterval); // Stop simulation on error
                },
                xhr: function() {
                    var xhr = new XMLHttpRequest();
                    
                    xhr.upload.addEventListener('progress', function(event) {
                        if (event.lengthComputable) {
                            var percentComplete = Math.round((event.loaded / event.total) * 100);
                            updateProgressBar(percentComplete);
                        } else {
                            console.log('Event lengthComputable is false');
                        }
                    });

                    xhr.addEventListener('loadstart', function(event) {
                        console.log('Upload started');
                        // Start progress simulation
                        startProgressSimulation();
                    });

                    xhr.addEventListener('loadend', function(event) {
                        console.log('Upload ended');
                        // Ensure progress reaches 100% on completion
                        updateProgressBar(50);
                        clearInterval(progressInterval); // Stop simulation on completion
                    });

                    return xhr;
                }
            });
        } else {
            alert("Please Select one Plugin !");
        }
    });

    function showProgressModal(){
        console.log('Loading..');

        var html = '<div id="importModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000; display: flex; justify-content: center; align-items: center;">';
            html += '<div class="modal-content" style="background: white; padding: 20px; border-radius: 5px; text-align: center;">';
            html += '<p>Importing...</p>';
            html += '<p>Please be patient and don’t refresh this page, the import process may take a while, this also depends on your server.</p>';
            html += '<div class="progress-bar" style="width: 100%; background: #f3f3f3; margin: 10px 0; height: 20px; border-radius: 5px; overflow: hidden;">';
            html += '<span id="progress" style="display: block; height: 100%; background: #4caf50; width: 0%;"></span>';
            html += '</div>';
            html += '<p>Import  <span id="plugin-progress">0%</span></p>';
            html += '</div>';
        html += '</div>';

        $('body').append(html);
        $('#importModal').fadeIn();
    }

    function updateProgressBar(progress) {
        $('#progress').css('width', progress + '%');
        $('#plugin-progress').text(progress + '%');
    }

    function startProgressSimulation() {
        var progress = 0;
        progressInterval = setInterval(function() {
            if (progress < 100) {
                progress += Math.random() * 5; // Increment progress in small steps
                if (progress > 100) progress = 100;
                updateProgressBar(Math.round(progress));
            } else {
                clearInterval(progressInterval);
            }
        }, 100); // Update progress every 100ms
    }

    function initialAjaxCall(configData, slug) {
        var data = new FormData();
        data.append('action', 'templify_import_initial');
        data.append('security', templify_importer_templates.ajax_nonce);
        data.append('override_colors', 'true');
        data.append('override_fonts', 'true');
        data.append('builder', 'custom');
        data.append('selected', configData);
        data.append('slug', slug);
        data.append('configData', configData);

        ajaxCall(data);
    }

    function ajaxCall(data) {
        $.ajax({
            method: 'POST',
            url: templify_importer_templates.ajax_url,
            data: data,
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(event) {
                    if (event.lengthComputable) {
                        var percentComplete = Math.round((event.loaded / event.total) * 100);
                        updateProgressBar(percentComplete);
                    } else {
                        console.log('Event lengthComputable is false');
                    }
                });

                xhr.addEventListener('loadstart', function(event) {
                    console.log('Upload started');
                    // Start progress simulation
                    startProgressSimulation();
                });

                xhr.addEventListener('loadend', function(event) {
                    console.log('Upload ended');
                    // Ensure progress reaches 100% on completion
                    updateProgressBar(100);
                    clearInterval(progressInterval); // Stop simulation on completion
                });

                return xhr;
            }
        })
        .done(function(response) {
            console.log(response);

            if (response.status === 'initialSuccess') {
                var newData = new FormData();
                newData.append('action', 'templify_import_demo_data');
                newData.append('security', templify_importer_templates.ajax_nonce);
                newData.append('override_colors', 'true');
                newData.append('override_fonts', 'true');
                newData.append('builder', 'custom');
                newData.append('selected', data.slug);
                newData.append('configData', data.configData);
                ajaxCall(newData);
            } else if (response.status === 'newAJAX') {
                console.log('newajax call');
                ajaxCall(data);
            } else if (response.status === 'customizerAJAX') {
                var newData = new FormData();
                newData.append('action', 'templify_import_customizer_data');
                newData.append('security', templify_importer_templates.ajax_nonce);
                newData.append('wp_customize', 'on');
                console.log('customizerajax call');
                ajaxCall(newData);
            } else if (response.status === 'afterAllImportAJAX') {
                var newData = new FormData();
                newData.append('action', 'templify_after_import_data');
                newData.append('security', templify_importer_templates.ajax_nonce);
                console.log('afterAllImportajax call');
                ajaxCall(newData);
            }
        });
    }
});
