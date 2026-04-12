( function() {
    "use strict";

    window.jetABAFMacrosInserter = {
        data() {
            return {
                currentMacros: {},
                editMacros: false,
                macrosList: window.JetABAFConfig.macros_list,
                isActive: false,
                result: {}
            };
        },
        methods: {
            applyMacros( macros, force ) {
                force = force || false;

                if ( macros ) {
                    this.$set( this.result, 'macros', macros.id );
                    this.$set( this.result, 'macrosName', macros.name );

                    if ( macros.controls ) {
                        this.$set( this.result, 'macrosControls', macros.controls );
                    }
                }

                if ( macros && ! force && macros.controls ) {
                    this.currentMacros = macros;
                    this.editMacros = true;

                    return;
                }

                this.$emit( 'input', this.formatResult() );

                this.isActive = false;
                this.result = {};

                this.resetEdit();
            },
            checkCondition( condition ) {
                let checkResult = true;

                condition = condition || {};

                for ( const [ fieldName, check ] of Object.entries( condition ) ) {
                    if ( check && check.length ) {
                        if ( ! check.includes( this.result[ fieldName ] ) ) {
                            checkResult = false;
                        }
                    } else {
                        if ( check !== this.result[ fieldName ] ) {
                            checkResult = false;
                        }
                    }
                }

                return checkResult;
            },
            formatResult() {
                if ( ! this.result.macros ) {
                    return;
                }

                let res = '%';

                res += this.result.macros;

                if ( this.result.macrosControls ) {
                    for ( const prop in this.result.macrosControls ) {
                        res += '|';

                        if ( undefined !== this.result[ prop ] ) {
                            res += this.result[ prop ];
                        }
                    }
                }

                res += '%';

                if ( this.result.advancedSettings && ( this.result.advancedSettings.fallback || this.result.advancedSettings.context ) ) {
                    res += JSON.stringify( this.result.advancedSettings );
                }

                return res;
            },
            getControlValue( control ) {
                if ( this.result[ control.name ] ) {
                    return this.result[ control.name ];
                } else if ( control.default ) {
                    this.setMacrosArg( control.default, control.name );
                    return control.default;
                }
            },
            getPreparedControls() {
                let controls = [];

                for ( const controlID in this.currentMacros.controls ) {
                    let control = this.currentMacros.controls[ controlID ];
                    let optionsList = [];
                    let type = control.type;
                    let label = control.label;
                    let defaultVal = control.default;
                    let groupsList = [];
                    let condition = control.condition || {};

                    switch ( control.type ) {
                        case 'text':
                            type = 'cx-vui-input';
                            break;

                        case 'select':
                            type = 'cx-vui-select';

                            if ( control.groups ) {
                                for ( let i = 0; i < control.groups.length; i++ ) {
                                    let group = control.groups[ i ];
                                    let groupOptions = [];

                                    for ( const optionValue in group.options ) {
                                        groupOptions.push( {
                                            value: optionValue,
                                            label: group.options[ optionValue ],
                                        } );
                                    }

                                    groupsList.push( {
                                        label: group.label,
                                        options: groupOptions,
                                    } );

                                }
                            } else {
                                for ( const optionValue in control.options ) {
                                    optionsList.push( {
                                        value: optionValue,
                                        label: control.options[ optionValue ],
                                    } );
                                }
                            }

                            break;

                        default:
                            break;
                    }

                    controls.push( {
                        type: type,
                        name: controlID,
                        label: label,
                        default: defaultVal,
                        optionsList: optionsList,
                        groupsList: groupsList,
                        condition: condition,
                        value: control.default,
                    } );
                }

                return controls;
            },
            onClickOutside() {
                this.isActive = false;
                this.resetEdit();
            },
            resetEdit() {
                this.currentMacros = {};
                this.editMacros = false;
            },
            setMacrosArg( value, arg ) {
                this.$set( this.result, arg, value );
            },
            switchIsActive() {
                this.isActive = ! this.isActive;

                if ( this.isActive ) {
                    if ( this.result.macros ) {
                        for (let i = 0; i < this.macrosList.length; i++ ) {
                            if ( this.result.macros === this.macrosList[ i ].id && this.macrosList[ i ].controls ) {
                                this.currentMacros = this.macrosList[ i ];
                                this.editMacros = true;
                            }
                        }
                    }
                } else {
                    this.resetEdit();
                }
            },
        }
    }
} ) ();