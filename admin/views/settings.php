<div class="wrap"><h1></h1></div>

<div class="swipego-page">
    <div class="p-4 pd:mx-0">
        <div class="container max-w-screen-xl m-auto space-y-7">
            <div class="flex flex-col sm:flex-row items-center">
                <div class="w-full mt-3 sm:mr-4 sm:mt-0">
                    <h1 class="text-3xl sm:text-4xl font-bold"><?php esc_html_e( 'Gravity Forms Settings', 'swipego' ); ?></h1>
                    <p class="text-gray-400 mt-1"><?php printf( __( 'For further information, please visit our website: <a href="%s" class="text-primary hover:text-purple-500" target="_blank">www.swipego.io</a>', 'swipego' ), 'https://swipego.io/' ); ?></p>
                </div>
                <img class="object-contain h-10 m-auto sm:mr-0 order-first sm:order-last" alt="<?php esc_attr_e( 'Swipe logo', 'swipego' ); ?>" src="<?php echo esc_attr( SWIPEGO_URL . 'assets/images/logo-swipe.svg' ); ?>">
            </div>

            <div class="flex flex-col">
                <div class="overflow-x-auto shadow-md sm:rounded-lg">
                    <div class="inline-block min-w-full align-middle">
                        <div class="overflow-hidden ">
                            <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th scope="col" class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase"><?php esc_html_e( 'Forms', 'swipego-gf' ); ?></th>
                                        <th scope="col" class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase"><?php esc_html_e( 'Settings', 'swipego-gf' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if ( $gateway_forms ) : ?>
                                        <?php foreach ( $gateway_forms as $form ) : ?>
                                            <tr class="hover:bg-gray-100">
                                                <td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                    <a href="<?php echo esc_attr( add_query_arg( 'id', $form['id'], $form_edit_url ) ); ?>" target="_blank"><?php echo esc_html( $form['title'] . ' (#' . $form['id'] . ')' ); ?></a>
                                                </td>
                                                <td class="py-4 px-6 text-sm font-medium text-gray-500 whitespace-nowrap">
                                                    <?php foreach ( $form['feeds'] as $feed ) : ?>
                                                        <a href="<?php echo esc_attr( add_query_arg( array( 'fid' => $form['id'], 'id' => $feed['id'] ), $feed_edit_url ) ); ?>" target="_blank"><?php echo esc_html( $feed['title'] ); ?></a><br>
                                                    <?php endforeach; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr class="hover:bg-gray-100">
                                            <td class="py-4 px-6 text-sm font-medium text-gray-500 whitespace-nowrap" colspan="2"><?php printf( __( 'No form found. <a class="text-gray-900" href="%s">Create one</a>', 'swipego-gf' ), admin_url( 'admin.php?page=gf_new_form' ) ); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
