import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { dasherize } from '@ember/string';
export default class AdminProductCategoryComponent extends Component {
    @service store;
    @service modalsManager;
    @service currentUser;
    @service modalsManager;
    @service notifications;
    @service fetch;
    @service hostRouter;
    @tracked categories = [];
    @tracked selectedCategory;
    @tracked isLoading = false;
    @tracked buttonTitle = null;

    constructor() {
        super(...arguments);
        this.category = this.args.category;
        this.fetchCategories();
    }

    @action async addCategory() {
        const category = this.store.createRecord('category', {
            for: 'pallet_product',
        });

        this.modalsManager.show('modals/create-product-category', {
            title: 'Create a new product category',
            acceptButtonIcon: 'check',
            acceptButtonIconPrefix: 'fas',
            declineButtonIcon: 'times',
            declineButtonIconPrefix: 'fas',
            category,
            uploadNewPhoto: (file) => {
                this.fetch.uploadFile.perform(
                    file,
                    {
                        path: `uploads/${category.company_uuid}/product-category-icon/${dasherize(category.name ?? this.currentUser.companyId)}`,
                        subject_uuid: category.id,
                        subject_type: `category`,
                        type: `category_icon`,
                    },
                    (uploadedFile) => {
                        category.setProperties({
                            icon_file_uuid: uploadedFile.id,
                            icon_url: uploadedFile.url,
                            icon: uploadedFile,
                        });
                    }
                );
            },
            confirm: (modal) => {
                modal.startLoading();

                return category.save().then(() => {
                    this.notifications.success('New product category created.');
                    return this.fetchCategories();
                });
            },
        });
    }

    @action async fetchCategories() {
        this.categories = await this.store.query('category', {
            for: 'pallet_product',
        });
    }

    @action async addSubCategory(category) {
        const subCategory = this.store.createRecord('category', {
            for: 'pallet_product',
            parent: category,
        });

        this.modalsManager.show('modals/create-product-category', {
            title: 'Create a new subcategory',
            acceptButtonIcon: 'check',
            acceptButtonIconPrefix: 'fas',
            declineButtonIcon: 'times',
            declineButtonIconPrefix: 'fas',
            category: subCategory,
            uploadNewPhoto: (file) => {
                this.fetch.uploadFile.perform(
                    file,
                    {
                        path: `uploads/${subCategory.company_uuid}/product-category-icon/${dasherize(subCategory.name ?? this.currentUser.companyId)}`,
                        subject_uuid: subCategory.id,
                        subject_type: `category`,
                        type: `category_icon`,
                    },
                    (uploadedFile) => {
                        subCategory.setProperties({
                            icon_file_uuid: uploadedFile.id,
                            icon_url: uploadedFile.url,
                            icon: uploadedFile,
                        });
                    }
                );
            },
            confirm: async (modal) => {
                modal.startLoading();

                try {
                    await subCategory.save();
                    this.notifications.success('New subcategory created.');
                    await this.fetchCategories();
                } catch (error) {
                    this.notifications.error('Error creating subcategory.');
                    console.error('Error creating subcategory:', error);
                } finally {
                    modal.stopLoading();
                }
            },
        });
    }

    @action async deleteCategory(category) {
        const confirmation = confirm(`Are you sure you want to delete the category "${category.name}"?`);

        if (confirmation) {
            try {
                await category.destroyRecord();
                this.notifications.success('Category deleted successfully.');
                await this.fetchCategories();
            } catch (error) {
                this.notifications.error('Error deleting category.');
                console.error('Error deleting category:', error);
            }
        }
    }
}
