const host = 'https://composites.lib.unb.ca'
describe('UNB Class Composite & Group Photographs', {baseUrl: host, groups: ['sites']}, () => {

  context('Front page', {baseUrl: host}, () => {
    beforeEach(() => {
      cy.visit('')
      cy.title()
        .should('contain', 'UNB Class Composite & Group Photographs')
    })

    specify('Search for "Max Aitken" should find 5+ results', () => {
      cy.get('form').within(() => {
        cy.get('input[type="text"]').type('Max Aitken')
      }).submit()
      cy.url()
        .should('match', /search-persons/)
      cy.get('.item-list ul li')
        .should('have.lengthOf.at.least', 5)
    })
  })
})
