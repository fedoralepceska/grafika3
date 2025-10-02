<template>
    <div v-if="show" class="validation-modal-overlay" @click="closeModal">
        <div class="validation-modal" @click.stop>
            <div class="modal-header">
                <h3><i class="fa fa-exclamation-triangle"></i> File Naming Error</h3>
                <button @click="closeModal" class="modal-close-btn">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            
            <div class="modal-content">
                <div class="error-info">
                    <p class="error-message">
                        File <strong>"{{ invalidFile.filename }}"</strong> has incorrect naming.
                    </p>
                    
                    <p v-if="invalidFile.errorMessage" class="specific-error">
                        {{ invalidFile.errorMessage }}
                    </p>
                    
                    <div class="suggestion">
                        <p><strong>Suggested filename:</strong></p>
                        <code class="suggested-filename" :title="invalidFile.suggestedFilename">{{ invalidFile.suggestedFilename }}</code>
                        <button @click="copySuggestion" class="copy-btn" :disabled="copyButtonDisabled">
                            <i class="fa fa-copy"></i> {{ copyButtonText }}
                        </button>
                    </div>
                </div>
                
                <div class="naming-rules">
                    <h4><i class="fa fa-info-circle"></i> File Naming Rules:</h4>
                    <div class="rules-list">
                        <div class="rule-item"><i class="fa fa-times-circle"></i> No spaces allowed</div>
                        <div class="rule-item"><i class="fa fa-times-circle"></i> No parentheses ( ) allowed</div>
                        <div class="rule-item"><i class="fa fa-times-circle"></i> No Cyrillic characters allowed</div>
                        <div class="rule-item"><i class="fa fa-times-circle"></i> No special characters: &lt;&gt;:"/\|?*[]{};&=$&#,~`!@%^+</div>
                        <div class="rule-item"><i class="fa fa-check-circle"></i> Use only letters, numbers, dots, hyphens (-), underscores (_)</div>
                        <div class="rule-item"><i class="fa fa-check-circle"></i> Keep filenames under 200 characters</div>
                    </div>
                </div>
                
                <div class="examples">
                    <h4><i class="fa fa-lightbulb-o"></i> Examples:</h4>
                    <div class="example-list">
                        <div class="example-item bad">
                            <i class="fa fa-times-circle"></i>
                            <span>"<code>my file (copy).pdf</code>"</span>
                        </div>
                        <div class="example-item bad">
                            <i class="fa fa-times-circle"></i>
                            <span>"<code>каталог продукции.pdf</code>"</span>
                        </div>
                        <div class="example-item good">
                            <i class="fa fa-check-circle"></i>
                            <span>"<code>my_file_copy.pdf</code>"</span>
                        </div>
                        <div class="example-item good">
                            <i class="fa fa-check-circle"></i>
                            <span>"<code>catalog_product.pdf</code>"</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button @click="closeModal" class="btn btn-primary">
                    <i class="fa fa-check"></i> I Understand
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FileNameValidationModal',
    props: {
        show: {
            type: Boolean,
            default: false
        },
        invalidFile: {
            type: Object,
            default: () => ({
                filename: '',
                errorMessage: '',
                suggestedFilename: ''
            })
        }
    },
    data() {
        return {
            copyButtonText: 'Copy',
            copyButtonDisabled: false
        };
    },
    methods: {
        closeModal() {
            this.$emit('close');
        },
        
        copySuggestion() {
            if (this.invalidFile.suggestedFilename && !this.copyButtonDisabled) {
                navigator.clipboard.writeText(this.invalidFile.suggestedFilename).then(() => {
                    // Update button text and disable temporarily
                    this.copyButtonText = 'Copied!';
                    this.copyButtonDisabled = true;
                    
                    // Optional: Show a toast notification
                    const event = new CustomEvent('show-toast', {
                        detail: {
                            message: 'Filename copied to clipboard!',
                            type: 'success'
                        }
                    });
                    window.dispatchEvent(event);
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        this.copyButtonText = 'Copy';
                        this.copyButtonDisabled = false;
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            }
        }
    }
};
</script>

<style scoped lang="scss">
.validation-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    backdrop-filter: blur(4px);
}

.validation-modal {
    background: white;
    border-radius: 12px;
    max-width: 90vw;
    max-height: 90vh;
    width: 600px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #dc3545, #c82333);

    h3 {
        margin: 0;
        font-size: 1.3rem;
        color: white;
        font-weight: 600;

        i {
            margin-right: 8px;
            color: #ffc107;
        }
    }
}

.modal-close-btn {
    background: none;
    border: none;
    font-size: 1.4rem;
    cursor: pointer;
    color: white;
    padding: 6px;
    border-radius: 6px;
    transition: all 0.2s ease;

    &:hover {
        background-color: rgba(255, 255, 255, 0.2);
        color: #ffc107;
    }
}

.modal-content {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
    max-height: 60vh;
}

.error-info {
    margin-bottom: 24px;
    padding: 20px;
    background-color: #f8f9fa;
    border-left: 4px solid #dc3545;
    border-radius: 0 6px 6px 0;

    .error-message {
        margin: 0 0 12px 0;
        font-size: 1.1rem;
        color: #dc3545;
        font-weight: 500;
    }

    .specific-error {
        margin: 0 0 16px 0;
        color: #6c757d;
        font-style: italic;
    }

    .suggestion {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 16px;
        min-width: 0; // Allow flex items to shrink

        p {
            margin: 0;
            color: #495057;
            font-weight: 500;
            flex-shrink: 0; // Prevent label from shrinking
        }

        .suggested-filename {
            flex: 1;
            min-width: 120px;
            max-width: 300px;
            background-color: #e9ecef;
            padding: 8px 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #495057;
            border: 2px solid #28a745;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            word-break: break-all;
        }

        .copy-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s ease;

            &:hover:not(:disabled) {
                background-color: #218838;
                transform: translateY(-1px);
            }

            &:disabled {
                background-color: #6c757d;
                cursor: not-allowed;
                transform: none;
                opacity: 0.8;
            }

            i {
                margin-right: 4px;
            }
        }
    }
}

.naming-rules {
    margin-bottom: 24px;

    h4 {
        margin: 0 0 16px 0;
        color: #495057;
        font-size: 1.1rem;
        font-weight: 600;

        i {
            margin-right: 8px;
            color: #007bff;
        }
    }

    .rules-list {
        margin: 0;
        padding: 0;
        
        .rule-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
            padding: 6px 0;
            font-size: 0.95rem;
            color: #495057;
            text-align: left;

            i.fa-times-circle {
                color: #dc3545;
                margin-right: 8px;
                margin-top: 2px;
                flex-shrink: 0;
            }

            i.fa-check-circle {
                color: #28a745;
                margin-right: 8px;
                margin-top: 2px;
                flex-shrink: 0;
            }
        }
    }
}

.examples {
    h4 {
        margin: 0 0 16px 0;
        color: #495057;
        font-size: 1.1rem;
        font-weight: 600;

        i {
            margin-right: 8px;
            color: #fd7e14;
        }
    }

    .example-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .example-item {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.9rem;

        &.bad {
            background-color: #fff5f5;
            border-left: 4px solid #dc3545;
            color: #dc3545;

            i {
                margin-right: 8px;
                color: #dc3545;
            }
        }

        &.good {
            background-color: #f0fff4;
            border-left: 4px solid #28a745;
            color: #28a745;

            i {
                margin-right: 8px;
                color: #28a745;
            }
        }

        code {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            padding: 2px 4px;
            border-radius: 3px;
            background-color: rgba(0, 0, 0, 0.05);
        }
    }
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
    display: flex;
    justify-content: flex-end;

    .btn {
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;

        &.btn-primary {
            background-color: #007bff;
            color: white;

            &:hover {
                background-color: #0056b3;
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
            }

            &:active {
                transform: translateY(0);
            }
        }
    }
}

// Responsive design
@media (max-width: 768px) {
    .validation-modal {
        width: 95vw;
        max-height: 95vh;
    }

    .suggestion {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;

        .suggested-filename {
            width: 100%;
            max-width: none;
            min-width: auto;
        }

        .copy-btn {
            align-self: flex-start;
        }
    }
}

@media (max-width: 480px) {
    .validation-modal {
        width: 98vw;
        margin: 10px;
    }
    
    .error-info,
    .naming-rules,
    .examples {
        margin-bottom: 16px;
    }
    
    .modal-content {
        padding: 16px;
    }
}
</style>
