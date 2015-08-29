require 'rails_helper'

RSpec.describe Permission, type: :model do
  it { should validate_presence_of :code_name }
  it { should validate_presence_of :description }
end
