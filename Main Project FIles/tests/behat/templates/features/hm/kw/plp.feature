@javascript
Feature: Test the PLP page

  Background:
    Given I am on "{product_listing_page_url}"
    And I wait for the page to load

#  @eng @prod
#  Scenario: As a Guest, I should be able to see the header and the footer
#    Then I should be able to see the header
#    And I should see the title and count of items
#    Then I should be able to see the footer
#    And I wait for AJAX to finish
#     #commenting the filters as not available on UAT
##    Then I scroll to the "#block-views-block-alshaya-product-list-block-1 .js-pager__items pager" element
##    When I follow "Load More"
##    And I wait for AJAX to finish
##    Then more items should get loaded
#    Then I should see "Filter"
#    Then I should see "Colour"
#    And I should see "Price"
#    Then I should see "Size"
#
#  @eng @prod
#  Scenario: As a Guest, I should be able to sort in ascending and descending order the list
#    #commenting the filters as not available on UAT
##    # User selects sorts listing on basis of "Name A to Z"
##    When I select "Name A to Z" from the filter "#edit-sort-bef-combine--wrapper"
##    And I wait for AJAX to finish
##    Then I should see results sorted in ascending order
##    # User selects sorts listing on basis of "Name Z to A"
##    When I select "Name Z to A" from the filter "#edit-sort-bef-combine--wrapper"
##    And I wait for AJAX to finish
##    Then I should see results sorted in descending order
#
#    # User selects sorts listing on basis of "Price High to Low"
#    When I select "Price High to Low" from the filter "#edit-sort-bef-combine--wrapper"
#    And I wait for AJAX to finish
#    Then I should see results sorted in descending price order
#    # User selects sorts listing on basis of "Price Low to High"
#    When I select "Price Low to High" from the filter "#edit-sort-bef-combine--wrapper"
#    And I wait for AJAX to finish
#    Then I should see results sorted in ascending price order
#
#  @eng
#  Scenario: As a Guest, I should be able to select a product in stock and complete the checkout journey
#    When I select a product from a product category
#    And I wait for the page to load
#    When I select a color for the product
#    And I wait for AJAX to finish
#    When I select a size for the product
#    And I wait for AJAX to finish
#    When I press "add to basket"
#    And I wait for AJAX to finish
#    When I go to "/cart"
#    And I wait for the page to load
#    And I press "checkout securely"
#    And I wait for the page to load
#    When I click the element with id "edit-checkout-guest-checkout-as-guest" on page
#    And I wait 10 seconds
#    And I wait for the page to load
#    And I fill in "edit-guest-delivery-home-address-shipping-given-name" with "Test"
#    And I fill in "edit-guest-delivery-home-address-shipping-family-name" with "Test"
#    When I enter a valid Email ID in field "edit-guest-delivery-home-address-shipping-organization"
#    And I fill in "edit-guest-delivery-home-address-shipping-mobile-number-mobile" with "55004455"
#    And I select "Abbasiya" from "edit-guest-delivery-home-address-shipping-administrative-area"
#    And I fill in "edit-guest-delivery-home-address-shipping-locality" with "Block A"
#    And I fill in "edit-guest-delivery-home-address-shipping-address-line1" with "Street B"
#    And I fill in "edit-guest-delivery-home-address-shipping-dependent-locality" with "Builing C"
#    And I press "deliver to this address"
#    And I wait for the page to load
#    When I check the "member_delivery_home[address][shipping_methods]" radio button with "Standard Delivery" value
#    And I wait for AJAX to finish
#    And I press "proceed to payment"
#    And I wait 10 seconds
#    And I wait for the page to load
#    When I select a payment option "payment_method_title_cashondelivery"
#    And I wait for AJAX to finish
#    Then I scroll to the ".c-footer" element
#    And I accept terms and conditions
#    And I press "place order"
#    And I wait for the page to load
#    Then I should see text matching "Thank you for shopping online with us, Test Test"
#    And I should see text matching "Your order number is "
#
#  @arabic @prod
#  Scenario: As a Guest, I should be able to see the header and the footer on Arabic site
#    When I follow "??????????????"
#    And I wait for the page to load
#    Then I should be able to see the header in Arabic
#    And I should see the title and count of items
#    Then I should be able to see the footer in Arabic
#     #Commenting the filters as not available on UAT
##    When I click the label for "#block-views-block-alshaya-product-list-block-1 > div > div > ul > li > a"
##    And I wait for AJAX to finish
##    Then more items should get loaded
#    Then I should see "?????? ??????"
#    Then I should see "??????????"
#    And I should see "??????????"
#    Then I should see "????????????"
#
#  @arabic
#  Scenario: As a Guest on Arabic site, I should be able to select a product in stock and complete the checkout journey
#    When I follow "??????????????"
#    And I wait for the page to load
#    When I select a product from a product category
#    And I wait for the page to load
#    When I select a color for the product
#    And I wait for AJAX to finish
#    When I select a size for the product
#    And I wait for AJAX to finish
#    When I press "?????? ?????? ?????????? ????????????"
#    And I wait for AJAX to finish
#    Then I click on "#block-cartminiblock a.cart-link" element
##    And I wait for the page to load
##    When I follow "??????????"
#    And I wait for the page to load
#    When I press "?????????? ???????????? ??????????"
#    And I wait for the page to load
#    When I click the element with id "edit-checkout-guest-checkout-as-guest" on page
#    And I wait 10 seconds
#    And I wait for the page to load
#    When I fill in "edit-guest-delivery-home-address-shipping-given-name" with "Test"
#    And I fill in "edit-guest-delivery-home-address-shipping-family-name" with "Test"
#    When I enter a valid Email ID in field "edit-guest-delivery-home-address-shipping-organization"
#    And I fill in "edit-guest-delivery-home-address-shipping-mobile-number-mobile" with "55004455"
#    When I select "????????????????" from "edit-guest-delivery-home-address-shipping-administrative-area"
#    And I fill in "edit-guest-delivery-home-address-shipping-locality" with "???????? A"
#    When I fill in "edit-guest-delivery-home-address-shipping-address-line1" with "???????????? ??"
#    And I fill in "edit-guest-delivery-home-address-shipping-dependent-locality" with "???????? C"
#    When I fill in "edit-guest-delivery-home-address-shipping-address-line2" with "2"
#    And I press "?????????? ?????? ?????? ??????????????"
#    And I wait for the page to load
#    When I press "???????? ??????????"
#    And I wait 10 seconds
#    And I wait for the page to load
#    When I select a payment option "payment_method_title_cashondelivery"
#    And I wait for AJAX to finish
#    And I accept terms and conditions
#    And I wait 10 seconds
#    When I press "?????? ??????????????"
#    And I wait for the page to load
#    Then I should see text matching "?????????? ?????????????? ???????? ?????? ?????????????? Test Test"
#    Then I should see "?????? ???????????? ????"
#
#  @eng @prod
#  Scenario: As a Guest, I should be able to select a product in stock and complete the checkout journey
#    When I select a product from a product category
#    And I wait for the page to load
#    When I select a color for the product
#    And I wait for AJAX to finish
#    When I select a size for the product
#    And I wait for AJAX to finish
#    When I press "add to basket"
#    And I wait for AJAX to finish
#    Then I click on "#block-cartminiblock a.cart-link" element
#    And I wait for the page to load
#    And I press "checkout securely"
#    And I wait for the page to load
#    When I click the element with id "edit-checkout-guest-checkout-as-guest" on page
#    And I wait 10 seconds
#    And I wait for the page to load
#    And I fill in "edit-guest-delivery-home-address-shipping-given-name" with "Test"
#    And I fill in "edit-guest-delivery-home-address-shipping-family-name" with "Test"
#    When I enter a valid Email ID in field "edit-guest-delivery-home-address-shipping-organization"
#    And I fill in "edit-guest-delivery-home-address-shipping-mobile-number-mobile" with "55004455"
#    And I select "Abbasiya" from "edit-guest-delivery-home-address-shipping-administrative-area"
#    And I fill in "edit-guest-delivery-home-address-shipping-locality" with "Block A"
#    And I fill in "edit-guest-delivery-home-address-shipping-address-line1" with "Street B"
#    And I fill in "edit-guest-delivery-home-address-shipping-dependent-locality" with "Builing C"
#    And I press "deliver to this address"
#    And I wait for the page to load
#    When I check the "member_delivery_home[address][shipping_methods]" radio button with "Standard Delivery" value
#    And I wait for AJAX to finish
#    And I wait 10 seconds
#    And I press "proceed to payment"
#    And I wait for the page to load
#    When I select a payment option "payment_method_title_cashondelivery"
#    And I wait 10 seconds
#    And I wait for AJAX to finish
#    And I accept terms and conditions
#
#  @arabic @prod
#  Scenario: As a Guest on Arabic site, I should be able to select a product in stock and complete the checkout journey
#    When I follow "??????????????"
#    And I wait for the page to load
#    When I select a product from a product category
#    And I wait for the page to load
#    When I select a color for the product
#    And I wait for AJAX to finish
#    When I select a size for the product
#    And I wait for AJAX to finish
#    When I press "?????? ?????? ?????????? ????????????"
#    And I wait for AJAX to finish
#    Then I click on "#block-cartminiblock a.cart-link" element
##    And I wait for the page to load
##    When I follow "??????????"
#    And I wait for the page to load
#    When I press "?????????? ???????????? ??????????"
#    And I wait for the page to load
#    When I click the element with id "edit-checkout-guest-checkout-as-guest" on page
#    And I wait 10 seconds
#    And I wait for the page to load
#    When I fill in "edit-guest-delivery-home-address-shipping-given-name" with "Test"
#    And I fill in "edit-guest-delivery-home-address-shipping-family-name" with "Test"
#    When I enter a valid Email ID in field "edit-guest-delivery-home-address-shipping-organization"
#    And I fill in "edit-guest-delivery-home-address-shipping-mobile-number-mobile" with "55004455"
#    When I select "????????????????" from "edit-guest-delivery-home-address-shipping-administrative-area"
#    And I fill in "edit-guest-delivery-home-address-shipping-locality" with "???????? A"
#    When I fill in "edit-guest-delivery-home-address-shipping-address-line1" with "???????????? ??"
#    And I fill in "edit-guest-delivery-home-address-shipping-dependent-locality" with "???????? C"
#    When I fill in "edit-guest-delivery-home-address-shipping-address-line2" with "2"
#    And I press "?????????? ?????? ?????? ??????????????"
#    And I wait for the page to load
#    When I press "???????? ??????????"
#    And I wait 10 seconds
#    And I wait for the page to load
#    When I select a payment option "payment_method_title_cashondelivery"
#    And I wait 10 seconds
#    And I accept terms and conditions
