<?php


// Afficher les champs personnalisés dans la page de profil utilisateur
function w2p_pipedrive_custom_user_profile_fields($user)
{
    $w2p_person_id = get_user_meta($user->ID, w2p_get_meta_key(W2P_CATEGORY["person"], 'id'), true);
    $w2p_organization_id = get_user_meta($user->ID, w2p_get_meta_key(W2P_CATEGORY["organization"], 'id'), true);
?>
    <h3>Pipedrive Information</h3>
    <p>These values will be automatically added during the first synchronization with Pipedrive. If there's an error in associating the person or organization ID, you can modify them here.</p>

    <table class="form-table">
        <tr>
            <th><label for="w2p_person_id">Pipedrive Person ID</label></th>
            <td>
                <input type="number" name="w2p_person_id" id="w2p_person_id" value="<?php echo esc_attr($w2p_person_id); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="w2p_organization_id">Pipedrive Organization ID</label></th>
            <td>
                <input type="number" name="w2p_organization_id" id="w2p_organization_id" value="<?php echo esc_attr($w2p_organization_id); ?>" class="regular-text" />
            </td>
        </tr>
    </table>
<?php
}
add_action('show_user_profile', 'w2p_pipedrive_custom_user_profile_fields');
add_action('edit_user_profile', 'w2p_pipedrive_custom_user_profile_fields');

// Enregistrer les valeurs des champs personnalisés
function save_w2p_pipedrive_custom_user_profile_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    update_user_meta($user_id, 'w2p_person_id', intval($_POST['w2p_person_id']));
    update_user_meta($user_id, 'w2p_organization_id', intval($_POST['w2p_organization_id']));
}
add_action('personal_options_update', 'save_w2p_pipedrive_custom_user_profile_fields');
add_action('edit_user_profile_update', 'save_w2p_pipedrive_custom_user_profile_fields');
